<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
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
        $invoice->load(['patient', 'visit.act', 'visit.payments', 'cashier']);
        $patient = $invoice->patient;
        $visit = $invoice->visit;

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
            'timeline' => $timeline,
        ]);
    }

    public function pay(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isPaid()) {
            $invoice->markAsPaid(Auth::user());

            $visit = $invoice->visit;
            $alreadySettled = $visit?->payments()->where('status', Payment::STATUS_PAID)->exists();

            if (! $alreadySettled) {
                $service = $visit?->act?->label ?? 'Consultation';

                $admissions = $visit->patient->admissions()
                    ->where('admitted_at', '>=', $visit->arrived_at)
                    ->get();

                if ($admissions->isNotEmpty()) {
                    $totalDays = 0;
                    foreach ($admissions as $admission) {
                        $endDate = $admission->discharged_at?->copy()->startOfDay() ?? now()->startOfDay();
                        $totalDays += $admission->admitted_at->copy()->startOfDay()->diffInDays($endDate) + 1;
                    }

                    if ($totalDays > 0) {
                        $service .= ' — Hospitalisation (' . $totalDays . ' jour(s))';
                    }
                }

                Payment::create([
                    'patient_id' => $invoice->patient_id,
                    'visit_id' => $invoice->visit_id,
                    'service' => $service,
                    'amount' => $invoice->patient_amount,
                    'method' => 'Espèces',
                    'status' => Payment::STATUS_PAID,
                    'cashier_id' => Auth::id(),
                ]);
            }
        }

        return redirect()
            ->route('caisse.index')
            ->with('success', "Panier #{$invoice->id} encaissé avec succès.");
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load(['patient', 'visit', 'cashier']);
        $invoice->markAsPrinted();

        return view('caisse.invoice_print', [
            'invoice' => $invoice,
        ]);
    }

    public function generateFromVisit(Visit $visit): Invoice
    {
        $invoice = Invoice::firstOrCreate([
            'visit_id' => $visit->id,
        ], [
            'patient_id' => $visit->patient_id,
            'status' => Invoice::STATUS_PENDING,
        ]);

        $invoice->recalculate();

        return $invoice;
    }
}
