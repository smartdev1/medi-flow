<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Act;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function index(): View
    {
        $filters = [
            ['label' => 'Tout', 'active' => true],
            ['label' => 'Assuré', 'active' => false],
            ['label' => 'Non assuré', 'active' => false],
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
            'coverage' => $visit->patient->coverage,
            'status' => $visit->status,
            'status_tone' => $visit->status_tone,
            'urgent' => $visit->urgent,
        ]);

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
            'waitingCount' => $todaysVisits->where('status', Visit::STATUS_WAITING)->count(),
            'urgentCount' => $todaysVisits->where('urgent', true)->count(),
            'occupancyRate' => $occupancyRate,
            'coverageOptions' => [
                'Assuré' => 'Assuré',
                'Non assuré' => 'Non assuré',
            ],
            'insuranceCompanies' => \App\Models\InsuranceCompany::orderBy('name')->get(),
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'acts' => Act::orderBy('category')->orderBy('label')->get(),
            'observationPatients' => $this->buildObservationCards(),
        ]);
    }

    private function buildObservationCards(): array
    {
        $observations = Admission::where('status', Admission::STATUS_OBSERVATION)
            ->whereNull('discharged_at')
            ->with(['patient', 'room'])
            ->orderBy('admitted_at', 'desc')
            ->get();

        return $observations->map(fn (Admission $admission) => [
            'name' => $admission->patient->full_name,
            'initials' => $admission->patient->initials,
            'code' => $admission->patient->code,
            'room' => $admission->room->number ?? '—',
            'ward' => $admission->room->ward ?? '—',
            'reason' => $admission->reason ?: 'Observation',
            'status' => $admission->status,
            'status_tone' => $admission->status_tone,
            'admitted_at' => $admission->admitted_at,
        ])->all();
    }
}