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
            ['label' => 'Non assuré', 'active' => false],
            ['label' => 'CMU', 'active' => false],
            ['label' => 'Assuré', 'active' => false],
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
                'Non assuré' => 'Non assuré',
                'CMU' => 'CMU',
                'Assuré' => 'Assuré',
            ],
            'orientationOptions' => [
                'Médecin généraliste' => 'Médecin généraliste',
                'Pédiatrie' => 'Pédiatrie',
                'Urgence' => 'Urgence',
                'Laboratoire' => 'Laboratoire',
                'Gynécologie' => 'Gynécologie',
                'ORL' => 'ORL',
                'Ophtalmologie' => 'Ophtalmologie',
                'Cardiologie' => 'Cardiologie',
                'Dermatologie' => 'Dermatologie',
                'Neurologie' => 'Neurologie',
                'Urologie' => 'Urologie',
                'Orthopédie / Traumatologie' => 'Orthopédie / Traumatologie',
                'Nutrition / Diététique' => 'Nutrition / Diététique',
                'Dentisterie' => 'Dentisterie',
            ],
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'insuranceCompanies' => \App\Models\InsuranceCompany::orderBy('name')->get(),
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }
}