<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Visit;
use App\Services\CashierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);
        $invoices = Invoice::with(['patient', 'visit'])
            ->orderByDesc('created_at')
            ->get();

        $pendingInvoices = $invoices->where('status', Invoice::STATUS_PENDING);
        $awaitingPaymentInvoices = $invoices->where('status', Invoice::STATUS_AWAITING_PAYMENT);
        $paidInvoices = $invoices->where('status', Invoice::STATUS_PAID);

        return view('caisse.invoices', [
            'pendingInvoices' => $pendingInvoices,
            'awaitingPaymentInvoices' => $awaitingPaymentInvoices,
            'paidInvoices' => $paidInvoices,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['patient.insuranceCompany', 'visit.act', 'visit.payments', 'cashier']);
        $patient = $invoice->patient;
        $visit = $invoice->visit;

        $user = Auth::user();
        $userName = $user?->name ?? 'Caissier';
        $userRole = $user?->role?->label() ?? 'Caisse';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        $steps = collect();

        if ($visit->act) {
            $steps->push([
                'time' => $visit->arrived_at,
                'label' => 'Consultation',
                'meta' => $visit->act->label,
                'amount' => $visit->act->base_price,
                'icon' => 'stethoscope',
            ]);
        }

        foreach ($patient->exams as $exam) {
            $steps->push([
                'time' => $exam->requested_at,
                'label' => 'Examen laboratoire',
                'meta' => $exam->name,
                'amount' => $exam->price,
                'icon' => 'beaker',
            ]);
        }

        foreach ($patient->admissions()
            ->where('admitted_at', '>=', $visit->arrived_at)
            ->with(['room', 'dayLines'])
            ->get() as $admission) {
            $admission->load('dayLines');
            foreach ($admission->dayLines as $dayLine) {
                $dayIndex = $dayLine->day_date->diffInDays($admission->admitted_at->copy()->startOfDay()) + 1;
                $isLastDay = $dayLine->day_date->eq($admission->discharged_at?->copy()->startOfDay());
                $label = $admission->discharged_at && $isLastDay
                    ? 'Fin d\'hospitalisation'
                    : 'Hospitalisation';

                $steps->push([
                    'time' => $dayLine->day_date,
                    'label' => $label,
                    'meta' => $admission->reason . ' — ' . $admission->room?->number . ' (Lit ' . $admission->bed_number . ') · ' . $dayLine->day_date->format('d/m/Y'),
                    'amount' => $dayLine->amount,
                    'icon' => 'building',
                ]);
            }
        }

        $timeline = $steps
            ->sortBy('time')
            ->values()
            ->all();

        return view('caisse.invoice_show', [
            'invoice' => $invoice,
            'timeline' => $timeline,
            'cashierSession' => CashierSession::where('cashier_id', $user?->id)
                ->active()
                ->orderByDesc('opened_at')
                ->first(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Encaissement d'une facture, total ou partiel.
     *
     * Passe obligatoirement par CashierService : c'est ce qui garantit qu'un
     * encaissement est rattaché à une vacation et à un fonds déclaré. Un
     * montant supérieur au solde restant est refusé, ici par la validation
     * puis, si le solde a bougé entre-temps, par Invoice::settle().
     */
    public function pay(Request $request, Invoice $invoice, CashierService $cashier): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->isPaid()) {
            return back()->with('error', 'Cette facture est déjà soldée.');
        }

        $invoice->recalculate();
        $invoice->refresh();

        $remaining = $invoice->remaining_amount;

        $validated = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1', 'max:'.$remaining],
            'method' => ['required', 'in:'.implode(',', array_keys(config('clinic.payment_methods')))],
        ]);

        $amount = (int) ($validated['amount'] ?? $remaining);

        try {
            $cashier->settleInvoice($invoice, $amount, $validated['method'], auth()->user());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('caisse.index')
            ->with('success', $amount >= $remaining
                ? "Facture #{$invoice->id} soldée."
                : "Encaissement partiel de {$amount} FCFA enregistré sur la facture #{$invoice->id}.");
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['patient', 'visit', 'cashier']);
        $invoice->markAsPrinted();

        return view('caisse.invoice_print', [
            'invoice' => $invoice,
        ]);
    }

    public function generateFromVisit(Visit $visit): Invoice
    {
        return Invoice::forVisit($visit);
    }
}
