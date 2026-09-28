<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\CashierSession;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashierSessionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CashierSession::class);

        $isDocteurGeneral = Auth::user()->hasRole('docteur_general');

        $query = CashierSession::query()
            ->with(['cashier'])
            ->withCount('payments')
            ->orderByDesc('opened_at');

        if (! $isDocteurGeneral) {
            $query->forCashier(Auth::id());
        }

        $sessions = $query->paginate(15)->withQueryString();

        return view('caisse.sessions.index', [
            'sessions' => $sessions,
        ]);
    }

    public function show(CashierSession $cashierSession): View
    {
        $this->authorize('view', $cashierSession);

        $cashierSession->load(['cashier', 'payments.patient', 'payments.cashier']);

        $variance = $cashierSession->cashVariance();
        $duration = $cashierSession->duration();

        return view('caisse.sessions.show', [
            'session' => $cashierSession,
            'variance' => $variance,
            'duration' => $duration,
        ]);
    }

    public function openForm(Request $request): View
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

        return view('caisse.sessions.open');
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

    public function closeForm(Request $request): View
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

        return view('caisse.sessions.close', [
            'session' => $activeSession,
            'openPaymentsCount' => $openPaymentsCount,
        ]);
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
}
