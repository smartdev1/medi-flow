<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Versement;
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

        $user = Auth::user();
        $userName = $user?->name ?? 'Caissier';
        $userRole = $user?->role?->label() ?? 'Caisse';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        $statusFilter = $request->string('status')->toString() ?: 'Tout';

        $filters = [
            ['label' => 'Tout', 'active' => $statusFilter === 'Tout'],
            ['label' => 'Payé', 'active' => $statusFilter === 'Payé'],
            ['label' => 'Partiellement payé', 'active' => $statusFilter === 'Partiellement payé'],
            ['label' => 'En attente', 'active' => $statusFilter === 'En attente'],
        ];

        $date = $this->resolveDate($request->string('date')->toString() ?: null);
        $isToday = $date->isToday();
        $cashierId = Auth::user()->hasRole('docteur_general') ? null : Auth::id();

        $payments = Payment::with(['patient', 'cashier', 'visit', 'invoice', 'session'])
            ->forDate($date)
            ->when($cashierId, fn ($query) => $query->forCashier($cashierId))
            ->orderByDesc('created_at')
            ->get();

        // Factures dont l'activité tombe aujourd'hui : créées aujourd'hui, ou
        // modifiées aujourd'hui parce qu'une ligne a été ajoutée au dossier.
        // Filtrer sur la seule date de création rendait invisible la
        // facturation d'un séjour ouvert la veille.
        $invoiceQuery = Invoice::with(['patient.insuranceCompany', 'visit.act', 'visit.payments.session'])
            ->activeOn($date)
            ->orderByDesc('updated_at');

        // Les compteurs portent sur le même périmètre que la liste affichée,
        // mais sont évalués en base : un scope s'applique à une requête, pas à
        // une collection déjà chargée.
        $pendingCount = (clone $invoiceQuery)->open()->count();
        $aPayer = (clone $invoiceQuery)->awaitingPayment()->count();
        $payes = (clone $invoiceQuery)->paid()->count();
        $paniersEnCours = (clone $invoiceQuery)->pending()->count();
        $partialCount = (clone $invoiceQuery)->where('status', Invoice::STATUS_PARTIAL)->count();

        $invoices = $invoiceQuery->get();

        $invoicedVisitIds = $invoices->pluck('visit_id')->filter()->unique();

        $panierRows = $invoices->map(function (Invoice $invoice) {
            $patient = $invoice->patient;
            $visit = $invoice->visit;
            // Le règlement est lu dans la collection déjà chargée : une requête
            // par ligne ferait autant d'allers-retours que de paniers à l'écran.
            $settlement = $invoice->isPaid()
                ? $visit?->payments?->filter->isSettled()->sortByDesc('created_at')->first()
                : null;

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
                'restant' => number_format($invoice->remaining_amount, 0, ',', ' ').' FCFA',
                'remaining_amount' => $invoice->remaining_amount,
                'status' => $invoice->status_label,
                'status_tone' => $invoice->status_tone,
                'caissier' => $settlement?->cashier?->name ?? $invoice->cashier?->name,
                'service' => $visit?->act?->label ?? 'Consultation',
                'montant_total' => $settlement?->formatted_amount ?? $invoice->formatted_patient_amount,
                'method' => $settlement?->method,
                'payment_id' => $settlement?->id,
                'can_encaisser' => ! $invoice->isPaid(),
                'can_receipt' => $invoice->isPaid() && $settlement,
                'is_paid' => $invoice->isPaid(),
                'closed' => (bool) $settlement?->session?->isClosed(),
                'closed_at' => $settlement?->session?->closed_at?->format('d/m/Y H:i'),
            ];
        });

        // Encaissements manuels (hors facture) — paiements sans facture liée.
        $manualRows = $payments
            ->filter(fn (Payment $payment) => ! $payment->invoice_id && ! $payment->isVoided())
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
                    'restant' => null,
                    'remaining_amount' => 0,
                    'status' => $payment->status,
                    'status_tone' => $payment->status_tone,
                    'caissier' => $payment->cashier?->name,
                    'service' => $payment->service,
                    'montant_total' => $payment->formatted_amount,
                    'method' => $payment->method,
                    'payment_id' => $payment->id,
                    'can_encaisser' => false,
                    'can_receipt' => $payment->status === Payment::STATUS_PAID,
                    'is_paid' => $payment->status === Payment::STATUS_PAID,
                    'closed' => (bool) $payment->session?->isClosed(),
                    'closed_at' => $payment->session?->closed_at?->format('d/m/Y H:i'),
                ];
            });

        $encaissements = $panierRows
            ->concat($manualRows)
            ->when($statusFilter !== 'Tout', fn ($rows) => $rows->where('status', $statusFilter))
            ->values();

        // Agrégats : seuls les encaissements réels mouvementent la caisse, et
        // l'encaissé est cumulé par vacation afin qu'une vacation de 22h à 6h
        // ne soit pas amputée de ses encaissements après minuit.
        $cashedInDay = $payments->filter->isSettled()->sum('amount');

        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->orderByDesc('opened_at')
            ->first();

        $canClose = $isToday && $activeSession !== null;

        return view('caisse.dashboard', [
            'filters' => $filters,
            'statusFilter' => $statusFilter,
            'encaissements' => $encaissements,
            'cashedInToday' => $cashedInDay,
            'pendingCount' => $pendingCount,
            'partialCount' => $partialCount,
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
            'openCount' => 0,
            'closedCount' => 0,
            'activeSession' => $activeSession,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function history(Request $request): View
    {
        $isDocteurGeneral = Auth::user()->hasRole('docteur_general');
        $cashierId = $isDocteurGeneral ? null : Auth::id();

        $user = Auth::user();
        $userName = $user?->name ?? 'Caissier';
        $userRole = $user?->role?->label() ?? 'Caisse';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

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
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function showDay(Request $request, string $date): View
    {
        $day = $this->resolveDate($date);
        $cashierId = Auth::user()->hasRole('docteur_general') ? null : Auth::id();

        $user = Auth::user();
        $userName = $user?->name ?? 'Caissier';
        $userRole = $user?->role?->label() ?? 'Caisse';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        // La journée se lit désormais à travers les vacations : c'est l'unité
        // qui encaisse réellement, et une vacation traverse minuit.
        $sessions = CashierSession::with('cashier')
            ->forDate($day)
            ->when($cashierId, fn ($query) => $query->forCashier($cashierId))
            ->orderBy('opened_at')
            ->get();

        $versements = Versement::with('cashier')
            ->whereDate('date', $day)
            ->when($cashierId, fn ($query) => $query->where('cashier_id', $cashierId))
            ->orderByDesc('deposited_at')
            ->get();

        return view('caisse.jour', [
            'currentDate' => $day,
            'sessions' => $sessions,
            'versements' => $versements,
            'totalAmount' => $sessions->sum(fn (CashierSession $session) => $session->settledAmount()),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function refresh(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $this->resolveDate($request->string('date')->toString() ?: null);

        $invoiceQuery = Invoice::with(['patient.insuranceCompany', 'visit.act'])
            ->activeOn($date)
            ->orderByDesc('updated_at');

        return response()->json([
            'pendingCount' => (clone $invoiceQuery)->open()->count(),
            'aPayer' => (clone $invoiceQuery)->awaitingPayment()->count(),
            'payes' => (clone $invoiceQuery)->paid()->count(),
            'paniersEnCours' => (clone $invoiceQuery)->pending()->count(),
            'partialCount' => (clone $invoiceQuery)->where('status', Invoice::STATUS_PARTIAL)->count(),
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
