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
}
