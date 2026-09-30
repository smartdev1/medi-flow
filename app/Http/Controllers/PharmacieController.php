<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
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
            'methodOptions' => [
                'Espèces' => 'Espèces',
                'Carte bancaire' => 'Carte bancaire',
                'Mobile Money' => 'Mobile Money',
                'Assurance CNAM' => 'Assurance CNAM',
                'CMU' => 'CMU',
            ],
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
}
