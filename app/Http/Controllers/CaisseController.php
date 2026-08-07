<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CaisseController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->string('status')->toString() ?: 'Tout';

        $filters = [
            ['label' => 'Tout', 'active' => $statusFilter === 'Tout'],
            ['label' => 'Payé', 'active' => $statusFilter === 'Payé'],
            ['label' => 'Partiellement payé', 'active' => $statusFilter === 'Partiellement payé'],
            ['label' => 'En attente', 'active' => $statusFilter === 'En attente'],
        ];

        $date = $this->resolveDate($request->string('date')->toString() ?: null);
        $isToday = $date->isToday();
        $cashierId = Auth::id(); // Utiliser l'ID du caissier connecté

        $payments = Payment::with('patient')
            ->forDate($date)
            ->forCashier($cashierId)
            ->when($statusFilter !== 'Tout', fn ($query) => $query->where('status', $statusFilter))
            ->orderByDesc('created_at')
            ->get();

        $transactions = $payments->map(fn (Payment $payment) => [
            'name' => $payment->patient->full_name,
            'initials' => $payment->patient->initials,
            'id' => $payment->patient->code,
            'payment_id' => $payment->id,
            'service' => $payment->service,
            'amount' => $payment->formatted_amount,
            'method' => $payment->method,
            'status' => $payment->status,
            'status_tone' => $payment->status_tone,
            'closed' => $payment->isClosed(),
            'closed_at' => $payment->closed_at?->format('H:i'),
            'cashier' => $payment->cashier?->name, // Ajouter le nom du caissier
        ]);

        $cashedInDay = $payments->where('status', 'Payé')->sum('amount');
        $pendingCount = $payments->whereIn('status', ['En attente', 'Partiellement payé'])->count();
        $partialCount = $payments->where('status', 'Partiellement payé')->count();
        $closedCount = $payments->whereNotNull('closed_at')->count();
        $openCount = $payments->count() - $closedCount;

        $canClose = $isToday && $openCount > 0;

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('caisse.dashboard', [
            'filters' => $filters,
            'transactions' => $transactions,
            'cashedInToday' => $cashedInDay,
            'pendingCount' => $pendingCount,
            'partialCount' => $partialCount,
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'methodOptions' => [
                'Espèces' => 'Espèces',
                'Carte bancaire' => 'Carte bancaire',
                'Mobile Money' => 'Mobile Money',
                'Assurance CNAM' => 'Assurance CNAM',
                'CMU' => 'CMU',
            ],
            'statusOptions' => [
                'Payé' => 'Payé',
                'Partiellement payé' => 'Partiellement payé',
                'En attente' => 'En attente',
            ],
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
            'currentDate' => $date,
            'isToday' => $isToday,
            'canCloseDay' => $canClose,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function history(Request $request): View
    {
        $cashierId = Auth::id(); // Utiliser l'ID du caissier connecté
        
        $closedDays = Payment::query()
            ->closed()
            ->where('cashier_id', $cashierId) // Filtrer par caissier connecté
            ->selectRaw('DATE(created_at) as day, COUNT(*) as transactions_count, SUM(amount) as total_amount, SUM(CASE WHEN status = "Payé" THEN amount ELSE 0 END) as paid_amount, SUM(CASE WHEN status IN ("En attente","Partiellement payé") THEN amount ELSE 0 END) as unpaid_amount, MAX(closed_at) as closed_at, closed_by')
            ->groupBy('day', 'closed_by')
            ->orderByDesc('day')
            ->paginate(15)
            ->withQueryString();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('caisse.historique', [
            'closedDays' => $closedDays,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function showDay(Request $request, string $date): View
    {
        $day = $this->resolveDate($date);
        $cashierId = Auth::id(); // Utiliser l'ID du caissier connecté

        $payments = Payment::with(['patient', 'closedBy'])
            ->forDate($day)
            ->forCashier($cashierId)
            ->orderBy('created_at')
            ->get();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('caisse.jour', [
            'currentDate' => $day,
            'payments' => $payments,
            'totalAmount' => $payments->where('status', 'Payé')->sum('amount'),
            'pendingCount' => $payments->whereIn('status', ['En attente', 'Partiellement payé'])->count(),
            'isClosed' => $payments->isNotEmpty() && $payments->every(fn (Payment $p) => $p->isClosed()),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    private function resolveDate(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Carbon::today();
    }
}
