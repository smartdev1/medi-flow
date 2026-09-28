<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Act;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        $act = ! empty($data['act_id']) ? Act::find($data['act_id']) : null;

        if (! $act) {
            $visit = $patient->visits()->active()->latest('arrived_at')->first();
            $act = ($visit && $visit->act_id) ? $visit->act : null;
        }

        $activeSession = \App\Models\CashierSession::where('cashier_id', Auth::id())->active()->first();

        if (! $activeSession) {
            return redirect()
                ->route('caisse.index')
                ->with('error', 'Vous devez ouvrir une session de caisse avant d\'encaisser un paiement.');
        }

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'visit_id' => $patient->visits()->latest('arrived_at')->value('id'),
            'act_id' => $act?->id,
            'service' => $act ? $act->label : $data['service'],
            'base_amount' => $data['base_amount'],
            'taux' => $data['taux'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => $data['status'],
            'cashier_id' => Auth::id(),
            'cashier_session_id' => $activeSession->id,
        ]);

        return redirect()
            ->route('caisse.index', ['date' => $payment->created_at->toDateString()])
            ->with('success', "Encaissement enregistré pour {$patient->full_name}.");
    }

    public function closeDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $cashierId = Auth::user()->hasRole('docteur_general') ? null : Auth::id();
        $now = now();

        Log::channel('deprecations')->warning('Deprecated: closeDay() called by user {user_id}. Use CashierSession instead.', ['user_id' => Auth::id()]);

        // Récupérer uniquement les paiements OUVERTS pour ce caissier et cette date
        $openPayments = Payment::whereDate('created_at', $date)
            ->where('cashier_id', $cashierId)
            ->open()
            ->get();

        if ($openPayments->isEmpty()) {
            return redirect()
                ->route('caisse.index', ['date' => $date->toDateString()])
                ->with('success', "Aucun paiement à clôturer pour le {$date->translatedFormat('d/m/Y')} (votre caisse).");
        }

        // Clôturer uniquement les paiements de ce caissier
        Payment::whereDate('created_at', $date)
            ->where('cashier_id', $cashierId)
            ->open()
            ->update([
                'closed_at' => $now,
                'closed_by' => Auth::id(),
            ]);

        return redirect()
            ->route('caisse.historique')
            ->with('success', "Votre caisse du {$date->translatedFormat('d/m/Y')} a été clôturée ({$openPayments->count()} paiement(s)).");
    }

    public function markAsPaid(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer'],
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        $this->authorize('markAsPaid', $payment);

        // Mark the payment as paid first
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Sync the associated Invoice status if one exists for the same visit
        if ($payment->visit_id) {
            $invoice = \App\Models\Invoice::where('visit_id', $payment->visit_id)
                ->where('status', '!=', Invoice::STATUS_PAID)
                ->first();

            if ($invoice) {
                $totalPaid = Payment::where('visit_id', $payment->visit_id)
                    ->where('status', Payment::STATUS_PAID)
                    ->sum('amount');

                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_amount' => $totalPaid,
                    'cashier_id' => Auth::id(),
                    'paid_at' => now(),
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Paiement marqué comme payé pour {$payment->patient->full_name}."]);
        }

        return back()
            ->with('success', "Paiement marqué comme payé pour {$payment->patient->full_name}.");
    }

    public function receipt($paymentId): View
    {
        $payment = Payment::where('id', $paymentId)->with(['patient', 'cashier', 'visit'])->firstOrFail();

        $this->authorize('view', $payment);

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        return view('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);
    }

    public function downloadReceipt($paymentId): Response
    {
        $payment = Payment::where('id', $paymentId)->with(['patient', 'cashier', 'visit'])->firstOrFail();

        $this->authorize('view', $payment);

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        $pdf = Pdf::loadView('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);

        return $pdf->download('recu-' . $payment->id . '.pdf');
    }
}
