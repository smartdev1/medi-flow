<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionTransfer;
use App\Models\User;
use App\Models\Versement;
use App\Services\CashierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, CashierService $cashier): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);
        $visit = $patient->visits()->active()->latest('arrived_at')->first();

        try {
            $payment = $cashier->recordManualPayment(auth()->user(), [
                'patient_id' => $patient->id,
                'visit_id' => $visit?->id,
                'service' => $data['service'],
                'base_amount' => $data['base_amount'] ?? null,
                'taux' => $data['taux'] ?? null,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'status' => $data['status'] ?? Payment::STATUS_PAID,
            ]);
        } catch (RuntimeException $e) {
            return redirect()->route('caisse.index')->with('error', $e->getMessage());
        }

        return redirect()
            ->route('caisse.index', ['date' => $payment->created_at->toDateString()])
            ->with('success', "Encaissement enregistré pour {$patient->full_name}.");
    }

    /**
     * Annulation d'un encaissement.
     *
     * La piste comptable est conservée : le paiement passe en « Annulé » et la
     * facture est rouverte, plutôt que d'être supprimé.
     */
    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        if ($payment->isVoided()) {
            return back()->with('error', 'Cet encaissement est déjà annulé.');
        }

        $invoice = $payment->invoice;

        $payment->update(['status' => Payment::STATUS_CANCELLED]);

        if ($invoice) {
            $remaining = max(0, (int) $invoice->paid_amount - (int) $payment->amount);

            $invoice->update([
                'paid_amount' => $remaining,
                'status' => $remaining > 0 ? Invoice::STATUS_PARTIAL : Invoice::STATUS_AWAITING_PAYMENT,
                'paid_at' => null,
            ]);
        }

        return back()->with('success', 'Encaissement annulé et facture rouverte.');
    }

    /**
     * Versement journalier : ce qui remonte effectivement à la caisse centrale.
     * Indépendant de la clôture de vacation, puisqu'il peut intervenir le
     * lendemain.
     */
    public function deposit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'exists:cashier_sessions,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = CashierSession::findOrFail($validated['session_id']);

        if (! $session->isClosed()) {
            return back()->with('error', 'La vacation doit être clôturée avant le versement.');
        }

        $available = $session->availableForRemittance();

        if ($validated['amount'] > $available) {
            return back()->with('error', "Le versement dépasse le disponible de la vacation ({$available} FCFA).");
        }

        $versement = Versement::create([
            'date' => today(),
            'cashier_id' => $session->cashier_id,
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'deposited_at' => now(),
            'deposited_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $versement->sessions()->attach($session->id);

        return back()->with('success', "Versement de {$versement->formatted_amount} enregistré.");
    }

    /**
     * Remise de fonds au caissier suivant (relevé de caisse). L'argent reste
     * au coffre : c'est la responsabilité qui change de main.
     */
    public function handOver(Request $request, CashierSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'to_cashier_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $session->isClosed()) {
            return back()->with('error', 'Clôturez d\'abord la vacation avant de transmettre le fonds.');
        }

        $available = $session->availableForRemittance();

        if ($validated['amount'] > $available) {
            return back()->with('error', "La remise dépasse le disponible de la vacation ({$available} FCFA).");
        }

        $target = User::where('id', $validated['to_cashier_id'])
            ->whereIn('role', ['caissier', 'docteur_general'])
            ->firstOrFail();

        SessionTransfer::create([
            'from_session_id' => $session->id,
            'to_cashier_id' => $target->id,
            'from_cashier_id' => $session->cashier_id,
            'amount' => $validated['amount'],
            'transferred_at' => now(),
            'counted_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Fonds de {$validated['amount']} FCFA transmis à {$target->name}.");
    }

    public function receipt($paymentId): View
    {
        $payment = Payment::with(['patient', 'cashier', 'visit', 'invoice'])->findOrFail($paymentId);

        $this->authorize('view', $payment);

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        return view('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);
    }

    public function downloadReceipt($paymentId): Response
    {
        $payment = Payment::with(['patient', 'cashier', 'visit', 'invoice'])->findOrFail($paymentId);

        $this->authorize('view', $payment);

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        $pdf = Pdf::loadView('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);

        return $pdf->download('recu-' . $payment->id . '.pdf');
    }
}
