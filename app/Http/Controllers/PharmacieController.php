<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentRefusedException;
use App\Http\Requests\StorePharmacySaleRequest;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\CashierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PharmacieController extends Controller
{
    public function index(): View
    {
        $orders = Prescription::with(['patient', 'items.medication', 'prescribedBy', 'dispensedBy'])
            ->orderBy('requested_at')
            ->get();

        $awaiting = $orders->where('status', Prescription::STATUS_AWAITING_DISPENSE)->values();

        $queue = $awaiting->map(fn (Prescription $order, int $index) => [
            'name' => $order->patient->full_name,
            'initials' => $order->patient->initials,
            'time' => $order->requested_at->format('H:i'),
            'meta' => $order->medications_summary,
            'urgent' => false,
            'selected' => $index === 0,
            'badge' => $order->hasSufficientStock() ? null : ['label' => 'Stock insuffisant', 'tone' => 'danger'],
        ]);

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('pharmacie.dashboard', [
            'queue' => $queue,
            'orders' => $orders,
            'awaitingCount' => $awaiting->count(),
            'shortageCount' => $awaiting->filter(fn (Prescription $order) => ! $order->hasSufficientStock())->count(),
            'dispensedCount' => $orders->where('status', Prescription::STATUS_DISPENSED)->count(),
            // Les moyens de paiement sont présentés comme une liste simple, et
            // non comme une clé/valeur : le sélecteur de l'application attend
            // des options indexées par valeur.
            'methodOptions' => array_values([
                'Espèces' => 'Espèces',
                'Carte bancaire' => 'Carte bancaire',
                'Mobile Money' => 'Mobile Money',
                'Assurance CNAM' => 'Assurance CNAM',
                'CMU' => 'CMU',
            ]),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function searchPatients(Request $request)
    {
        $query = $request->string('q')->toString();

        $patients = Patient::query()
            ->when($query, function ($q, $query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'code', 'coverage']);

        return response()->json($patients->map(fn ($patient) => [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'code' => $patient->code,
            'coverage' => $patient->coverage,
        ]));
    }

    public function searchMedications(Request $request)
    {
        $query = $request->string('q')->toString();

        $medications = Medication::query()
            ->when($query, fn ($q, $query) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'unit', 'unit_price', 'stock_quantity']);

        return response()->json($medications);
    }

    /**
     * Enregistre une vente de médicaments au point de vente.
     *
     * Le prix est résolu par `CashierService` depuis le catalogue, jamais lu
     * dans la requête : un montant saisi au navigateur pourrait être inférieur
     * au coût réel. Le stock est décrémenté dans la même transaction que
     * l'encaissement, donc une vente refusée pour stock insuffisant ne laisse
     * aucun paiement derrière elle.
     */
    public function sell(StorePharmacySaleRequest $request, CashierService $cashier): RedirectResponse
    {
        $patient = Patient::query()->findOrFail($request->validated('patient_id'));

        try {
            $payment = $cashier->recordPharmacySale(Auth::user(), [
                'patient_id' => $patient->id,
                'service' => 'Vente pharmacie',
                'method' => $request->validated('method'),
                'items' => $request->validated('items'),
            ]);
        } catch (PaymentRefusedException $exception) {
            return redirect()
                ->route('pharmacie.index')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('pharmacie.index')
            ->with('success', 'Vente encaissée pour '.$patient->full_name.' ('.number_format($payment->amount, 0, ',', ' ').' FCFA).');
    }
}
