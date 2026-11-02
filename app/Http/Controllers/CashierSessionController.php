<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\Payment;
use App\Support\ClinicMetrics;
use App\Traits\ResolvesPeriod;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CashierSessionController extends Controller
{
    use ResolvesPeriod;
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CashierSession::class);

        $isSupervisor = Auth::user()->hasRole(Role::DocteurGeneral) || Auth::user()->hasRole(Role::Raf);

        $query = CashierSession::query()
            ->with(['cashier'])
            ->withCount('payments')
            ->orderByDesc('opened_at');

        if (! $isSupervisor) {
            $query->forCashier(Auth::id());
        }

        $sessions = $query->paginate(15)->withQueryString();

        return view('caisse.sessions.index', array_merge($this->layout(), [
            'sessions' => $sessions,
        ]));
    }

    public function show(CashierSession $cashierSession): View
    {
        $this->authorize('view', $cashierSession);

        $cashierSession->load(['cashier', 'payments.patient']);

        $variance = $cashierSession->cashVariance();
        $duration = $cashierSession->duration();
        $isRaf = Auth::user()->hasRole(Role::Raf);

        return view('caisse.sessions.show', array_merge($this->layout(), [
            'session' => $cashierSession,
            'variance' => $variance,
            'duration' => $duration,
            'isRaf' => $isRaf,
        ]));
    }

    public function openForm(Request $request): View|RedirectResponse
    {
        $this->authorize('create', CashierSession::class);

        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->first();

        if ($activeSession) {
            return redirect()
                ->route('caisse.index')
                ->with('error', 'Vous avez déjà une session de caisse en cours. Fermez-la avant d\'en ouvrir une nouvelle.');
        }

        return view('caisse.sessions.open', $this->layout());
    }

    public function open(Request $request): RedirectResponse
    {
        $this->authorize('create', CashierSession::class);

        $validated = $request->validate([
            'opening_balance' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->first();

        if ($activeSession) {
            return redirect()
                ->route('caisse.index')
                ->with('error', 'Vous avez déjà une session de caisse en cours.');
        }

        $session = CashierSession::create([
            'cashier_id' => Auth::id(),
            'opened_at' => now(),
            'opening_balance' => $validated['opening_balance'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('caisse.index')
            ->with('success', "Session de caisse ouverte avec un fond de caisse de ".number_format($validated['opening_balance'], 0, ',', ' ')." FCFA.");
    }

    public function closeForm(Request $request): View|RedirectResponse
    {
        /** @var CashierSession|null $activeSession */
        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->first();

        if (! $activeSession) {
            return redirect()
                ->route('caisse.index')
                ->with('error', 'Aucune session de caisse en cours à fermer.');
        }

        $this->authorize('update', $activeSession);

        $openPaymentsCount = $activeSession->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIAL])
            ->count();

        $theoreticalBalance = $activeSession->theoreticalBalance();
        $settledPayments = $activeSession->payments()
            ->settled()
            ->with(['patient', 'act'])
            ->get();

        return view('caisse.sessions.close', array_merge($this->layout(), [
            'session' => $activeSession,
            'openPaymentsCount' => $openPaymentsCount,
            'theoreticalBalance' => $theoreticalBalance,
            'settledPayments' => $settledPayments,
        ]));
    }

    public function close(Request $request): RedirectResponse
    {
        /** @var CashierSession|null $activeSession */
        $activeSession = CashierSession::where('cashier_id', Auth::id())
            ->active()
            ->first();

        if (! $activeSession) {
            return redirect()
                ->route('caisse.index')
                ->with('error', 'Aucune session de caisse en cours à fermer.');
        }

        $this->authorize('update', $activeSession);

        $validated = $request->validate([
            'closing_balance' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeSession->update([
            'closed_at' => now(),
            'closing_balance' => $validated['closing_balance'],
            'notes' => $validated['notes'] ?? $activeSession->notes,
        ]);

        $variance = $activeSession->cashVariance();
        $varianceMessage = $variance !== null
            ? " Écart de caisse : ".number_format($variance, 0, ',', ' ')." FCFA."
            : '';

        return redirect()
            ->route('caisse.index')
            ->with('success', "Session de caisse fermée avec un fond de clôture de ".number_format($validated['closing_balance'], 0, ',', ' ')." FCFA.".$varianceMessage);
    }

    public function emergencyCloseForm(CashierSession $cashierSession): View|RedirectResponse
    {
        $this->authorize('update', $cashierSession);

        if (! $cashierSession->canBeEmergencyClosed()) {
            return redirect()
                ->route('caisse.sessions.index')
                ->with('error', 'Cette session ne peut pas être fermée en urgence.');
        }

        return view('caisse.sessions.emergency-close', array_merge($this->layout(), [
            'session' => $cashierSession,
        ]));
    }

    public function emergencyClose(Request $request, CashierSession $cashierSession): RedirectResponse
    {
        $this->authorize('update', $cashierSession);

        $validated = $request->validate([
            'emergency_closure_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $cashierSession->canBeEmergencyClosed()) {
            return redirect()
                ->route('caisse.sessions.index')
                ->with('error', 'Cette session ne peut pas être fermée en urgence.');
        }

        $cashierSession->update([
            'closed_at' => now(),
            'emergency_closed_by' => Auth::id(),
            'emergency_closed_at' => now(),
            'emergency_closure_reason' => $validated['emergency_closure_reason'],
        ]);

        return redirect()
            ->route('caisse.sessions.index')
            ->with('success', 'Session fermée en urgence. En attente d\'audit.');
    }

    /**
     * RAF dashboard — financial indicators across all cashier sessions.
     */
    public function rafDashboard(Request $request): View
    {
        $this->authorize('viewAny', CashierSession::class);

        $period = $request->string('period')->toString() ?: 'month';
        [$from, $to, $periodLabel] = $this->resolvePeriod($period);

        $finance = ClinicMetrics::finance($from, $to);
        $monthlyRevenue = ClinicMetrics::monthlyRevenue();

        $periods = [
            ['key' => 'today', 'label' => "Aujourd'hui"],
            ['key' => '7d', 'label' => '7 jours'],
            ['key' => '30d', 'label' => '30 jours'],
            ['key' => 'month', 'label' => 'Ce mois'],
            ['key' => 'year', 'label' => 'Cette année'],
        ];

        $user = Auth::user();
        $userName = $user?->name ?? 'RAF';
        $userRole = $user?->role?->label() ?? 'Supervision financière';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('caisse.raf.dashboard', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'periods' => $periods,
            'finance' => $finance,
            'monthlyRevenue' => $monthlyRevenue,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Identité affichée par le cadre commun des écrans de vacation.
     *
     * Les quatre vues la consomment ; sans elle, la page ne s'affiche pas.
     */
    private function layout(): array
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Caissier';

        return [
            'userName' => $userName,
            'userRole' => $user?->role?->label() ?? 'Caisse',
            'userInitials' => $user?->initials ?? $this->initialsFor($userName),
        ];
    }
}
