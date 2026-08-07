<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\MedicationOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LogistiqueController extends Controller
{
    public function index(): View
    {
        $medications = Medication::orderBy('name')->get();
        $orders = MedicationOrder::with(['items.medication', 'requestedBy'])->latest('ordered_at')->get();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('logistique.dashboard', [
            'medications' => $medications,
            'orders' => $orders,
            'lowStockCount' => $medications->filter(fn (Medication $medication) => $medication->is_low_stock)->count(),
            'pendingOrderCount' => $orders->where('status', MedicationOrder::STATUS_PENDING)->count(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }
}
