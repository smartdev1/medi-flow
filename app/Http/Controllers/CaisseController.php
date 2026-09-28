<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CaisseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Payment::class);

        $statusFilter = $request->string('status')->toString() ?: 'Tout';

        $filters = [
            ['label' => 'Tout', 'active' => $statusFilter === 'Tout'],
            ['label' => 'Payé', 'active' => $statusFilter === Payment::STATUS_PAID],
            ['label' => 'Partiellement payé', 'active' => $statusFilter === Payment::STATUS_PARTIAL],
            ['label' => 'En attente', 'active' => $statusFilter === Payment::STATUS_PENDING],
        ];

        $date = $this->resolveDate($request->string('date')->toString() ?: null);
        $isToday = $date->isToday();
        $cashierId = Auth::user()->hasRole('docteur_general') ? null : Auth::id();

        $payments = Payment::with(['patient', 'cashier', 'visit'])
            ->forDate($date)
            ->when($cashierId, fn ($query) => $query->forCashier($cashierId))
            ->orderByDesc('created_at')
            ->get();

        // Paniers (Invoice) du jour — un par visite, fusionnés avec leur encaissement si payé.
        $invoices = Invoice::with(['patient.insuranceCompany', 'visit.act', 'visit.payments'])
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->get();

        $invoicedVisitIds = $invoices->pluck('visit_id')->filter()->unique();

        $panierRows = $invoices->map(function (Invoice $invoice) {
            $patient = $invoice->patient;
            $visit = $invoice->visit;
            $settlement = $invoice->isPaid()
                ? $visit?->payments()->where('status', Payment::STATUS_PAID)->latest()->first()
                : null;

            $statusLabel = $invoice->isPaid()
                ? ($settlement?->status ?? Payment::STATUS_PAID)
                : match ($invoice->status) {
                    Invoice::STATUS_AWAITING_PAYMENT => 'À payer',
                    default => 'En cours',
                };

            return [
                'type' => 'panier',
                'invoice_id' => $invoice->id,
                'invoice_status' => $invoice->status,
                'patient_name' => $patient->full_name,
                'initials' => $patient->initials,
                'patient_code' => $patient->code,
                'coverage' => $patient->insuranceCompany?->name ?? $patient->coverage,
                'subtotal' => $invoice->formatted_subtotal,
                'insurance' => $invoice->formatted_insurance_amount,
                'a_payer' => $invoice->formatted_patient_amount,
                'status' => $statusLabel,
                'status_tone' => $invoice->status_tone,
                'caissier' => $settlement?->cashier?->name,
                'service' => $settlement?->service ?? ($visit?->act?->label ?? 'Consultation'),
                'montant_total' => $settlement ? $settlement->formatted_amount : $invoice->formatted_patient_amount,
                'method' => $settlement?->method,
                'payment_id' => $settlement?->id,
                'can_encaisser' => ! $invoice->isPaid(),
                'can_receipt' => $invoice->isPaid() && $settlement,
                'closed' => $settlement?->isClosed() ?? false,
                'closed_at' => $settlement?->closed_at?->format('H:i'),
                'is_paid' => $invoice->isPaid(),
            ];
        });

        // Encaissements manuels (hors consultation) — visites sans panier lié.
        $manualRows = $payments
            ->filter(fn (Payment $payment) => ! $payment->visit_id || ! $invoicedVisitIds->contains($payment->visit_id))
            ->map(function (Payment $payment) {
                $patient = $payment->patient;

                return [
                    'type' => 'encaissement',
                    'invoice_id' => null,
                    'patient_name' => $patient->full_name,
                    'initials' => $patient->initials,
                    'patient_code' => $patient->code,
                    'coverage' => $patient->insuranceCompany?->name ?? $patient->coverage,
                    'subtotal' => null,
                    'insurance' => null,
                    'a_payer' => null,
                    'status' => $payment->status,
                    'status_tone' => $payment->status_tone,
                    'caissier' => $payment->cashier?->name,
                    'service' => $payment->service,
                    'montant_total' => $payment->formatted_amount,
                    'method' => $payment->method,
                    'payment_id' => $payment->id,
                    'can_encaisser' => in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL], true),
                    'can_receipt' => $payment->status === Payment::STATUS_PAID && ! $payment->isClosed(),
                    'closed' => $payment->isClosed(),
                    'closed_at' => $payment->closed_at?->format('H:i'),
                    'is_paid' => $payment->status === Payment::STATUS_PAID,
                ];
            });

        $encaissements = $panierRows
            ->concat($manualRows)
            ->when($statusFilter !== 'Tout', fn ($rows) => $rows->where('status', function ($row) use ($statusFilter) {
                if ($row['type'] === 'panier') {
                    $map = [
                        Invoice::STATUS_PENDING => 'En cours',
                        Invoice::STATUS_AWAITING_PAYMENT => 'À payer',
                        Invoice::STATUS_PAID => 'Payé',
                    ];
                    return ($map[$row['invoice_status']] ?? '') === $statusFilter
                        || $row['status'] === $statusFilter;
                }
                return $row['status'] === $statusFilter;
            }))
            ->values();

        $cashedInDay = $payments->where('status', Payment::STATUS_PAID)->sum('amount');
        $pendingCount = $payments->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL])->count();
        $closedCount = $payments->whereNotNull('closed_at')->count();
        $openCount = $payments->count() - $closedCount;

        $canClose = $isToday && $openCount > 0;

        $paniersEnCours = $invoices->where('status', Invoice::STATUS_PENDING)->count();
        $aPayer = $invoices->where('status', Invoice::STATUS_AWAITING_PAYMENT)->count();
        $payes = $invoices->where('status', Invoice::STATUS_PAID)->count();

        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->first();

        return view('caisse.dashboard', [
            'filters' => $filters,
            'statusFilter' => $statusFilter,
            'encaissements' => $encaissements,
            'cashedInToday' => $cashedInDay,
            'pendingCount' => $pendingCount,
            'partialCount' => $payments->where('status', Payment::STATUS_PARTIAL)->count(),
            'paniersEnCours' => $paniersEnCours,
            'aPayer' => $aPayer,
            'payes' => $payes,
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'patientActs' => $this->patientActsForJs(),
            'methodOptions' => config('clinic.payment_methods'),
            'statusOptions' => config('clinic.payment_statuses'),
            'currentDate' => $date,
            'isToday' => $isToday,
            'canCloseDay' => $canClose,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'activeSession' => $activeSession,
        ]);
    }

    public function history(Request $request): View
    {
        $isDocteurGeneral = Auth::user()->hasRole('docteur_general');
        $cashierId = $isDocteurGeneral ? null : Auth::id();

        $query = CashierSession::query()
            ->with(['cashier'])
            ->withCount('payments')
            ->closed()
            ->orderByDesc('opened_at');

        if ($cashierId) {
            $query->forCashier($cashierId);
        }

        $sessions = $query->paginate(15)->withQueryString();

        return view('caisse.historique', [
            'sessions' => $sessions,
            'cashierName' => $isDocteurGeneral ? 'Tous' : (auth()->user()?->name ?? 'Utilisateur'),
        ]);
    }

    public function showDay(Request $request, string $date): View
    {
        $day = $this->resolveDate($date);
        $cashierId = Auth::user()->hasRole('docteur_general') ? null : Auth::id();

        $payments = Payment::with(['patient', 'closedBy', 'cashier'])
            ->forDate($day)
            ->when($cashierId, fn ($query) => $query->forCashier($cashierId))
            ->orderBy('created_at')
            ->get();

        return view('caisse.jour', [
            'currentDate' => $day,
            'payments' => $payments,
            'totalAmount' => $payments->where('status', Payment::STATUS_PAID)->sum('amount'),
            'pendingCount' => $payments->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL])->count(),
            'isClosed' => $payments->isNotEmpty() && $payments->every(fn (Payment $p) => $p->isClosed()),
        ]);
    }

    private function resolveDate(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Carbon::today();
    }

    /**
     * Map patient_id => acte enregistré sur sa visite active (pour pré-remplir la caisse).
     */
    private function patientActsForJs(): array
    {
        return Visit::active()
            ->with('act')
            ->whereNotNull('act_id')
            ->get()
            ->groupBy('patient_id')
            ->map(function ($visits) {
                $visit = $visits->sortByDesc('arrived_at')->first();

                return $visit->act ? [
                    'label' => $visit->act->label,
                    'base' => $visit->act->base_price,
                    'insured' => $visit->act->insured_price,
                ] : null;
            })
            ->filter()
            ->all();
    }
}
