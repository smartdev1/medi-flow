<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
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

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        $payment = Payment::create([
            'patient_id' => $patient->id,
            'visit_id' => $patient->visits()->latest('arrived_at')->value('id'),
            'service' => $data['service'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => $data['status'],
            'cashier_id' => Auth::id(),
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
        $cashierId = Auth::id(); // Utiliser l'ID du caissier connecté
        $now = now();

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

        if ($payment->cashier_id !== null && $payment->cashier_id !== Auth::id()) {
            abort(403, "Ce paiement appartient à un autre caissier.");
        }

        $payment->update([
            'status' => 'Payé',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Paiement marqué comme payé pour {$payment->patient->full_name}."]);
        }

        return back()
            ->with('success', "Paiement marqué comme payé pour {$payment->patient->full_name}.");
    }

    public function receipt($paymentId): View
    {
        $payment = Payment::where('id', $paymentId)->with(['patient', 'cashier', 'visit'])->firstOrFail();

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        return view('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);
    }

    public function downloadReceipt($paymentId): Response
    {
        $payment = Payment::where('id', $paymentId)->with(['patient', 'cashier', 'visit'])->firstOrFail();

        $payment->receipt_number = sprintf('R-%06d', $payment->id);

        $pdf = Pdf::loadView('caisse.receipt', [
            'payment' => $payment,
            'clinicName' => config('app.name', 'Clinique'),
        ]);

        return $pdf->download('recu-' . $payment->id . '.pdf');
    }
}
