<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function index(): View
    {
        $filters = [
            ['label' => 'Tout', 'active' => true],
            ['label' => 'Prive', 'active' => false],
            ['label' => 'CMU', 'active' => false],
            ['label' => 'Assurance', 'active' => false],
        ];

        $todaysVisits = Visit::with('patient')
            ->whereDate('arrived_at', today())
            ->active()
            ->orderBy('arrived_at')
            ->get();

        $patients = $todaysVisits->map(fn (Visit $visit) => [
            'name' => $visit->patient->full_name,
            'initials' => $visit->patient->initials,
            'id' => $visit->patient->code,
            'arrival' => $visit->arrived_at->format('H:i'),
            'orientation' => $visit->orientation,
            'coverage' => $visit->patient->coverage,
            'status' => $visit->status,
            'status_tone' => $visit->status_tone,
            'urgent' => $visit->urgent,
        ]);

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        // Calcul du taux d'occupation
        $rooms = Room::where('maintenance', false)->with('activeAdmissions')->get();
        $totalCapacity = $rooms->sum('capacity');
        $totalOccupied = $rooms->sum('occupied_count');
        $occupancyRate = $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100) : 0;

        return view('accueil.dashboard', [
            'today' => now()->translatedFormat('l j F'),
            'filters' => $filters,
            'patients' => $patients,
            'arrivalsToday' => $todaysVisits->count(),
            'waitingCount' => $todaysVisits->where('status', 'En attente')->count(),
            'urgentCount' => $todaysVisits->where('urgent', true)->count(),
            'occupancyRate' => $occupancyRate,
            'coverageOptions' => [
                'Prive' => 'Prive',
                'CMU' => 'CMU',
                'Assurance' => 'Assurance',
            ],
            'orientationOptions' => [
                'Medecin generaliste' => 'Medecin generaliste',
                'Pediatrie' => 'Pediatrie',
                'Urgence' => 'Urgence',
                'Laboratoire' => 'Laboratoire',
            ],
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }
}