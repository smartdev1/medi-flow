<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Referral;
use App\Models\Visit;
use App\Support\PatientTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MedecinController extends Controller
{
    private const DEFAULT_VITALS = [
        'Médecin généraliste' => ['Tension', 'Température', 'Pouls', 'Saturation'],
        'Urgence' => ['Tension', 'Température', 'Pouls', 'Saturation'],
        'Pédiatrie' => ['Température', 'Pouls', 'Saturation', 'Poids'],
    ];

    public function index(Request $request): View
    {
        /** @var \App\Models\User $doctor */
        $doctor = Auth::user();

        $orientations = match ($doctor->role) {
            Role::Pediatre => ['Pédiatrie'],
            Role::Medecin => ['Médecin généraliste', 'Urgence'],
            default => ['Médecin généraliste', 'Urgence', 'Pédiatrie'], // admin : supervision
        };

        $visits = Visit::with(['patient', 'consultations', 'patient.admissions'])
            ->active() // Utilise la portée pour filtrer les visites actives
            ->whereIn('orientation', $orientations)
            ->orderBy('arrived_at')
            ->get()
            ->reject(function (Visit $visit) {
                $latest = $visit->consultations->sortByDesc('started_at')->first();

                return $latest && $latest->status === Consultation::STATUS_CLOSED;
            })
            ->reject(function (Visit $visit) {
                // Exclure les patients qui ont une admission active (hospitalisation en cours)
                return $visit->patient->admissions()->whereNull('discharged_at')->exists();
            })
            ->values();

        $selectedVisit = $request->integer('patient')
            ? $visits->firstWhere('patient_id', $request->integer('patient'))
            : $visits->first();

        $queue = $visits->map(fn (Visit $visit) => [
            'name' => $visit->patient->full_name,
            'initials' => $visit->patient->initials,
            'time' => $visit->arrived_at->diffForHumans(null, true).' d\'attente',
            'meta' => $visit->reason ?: $visit->orientation,
            'urgent' => $visit->urgent,
            'selected' => $selectedVisit && $visit->id === $selectedVisit->id,
            'href' => route('medecin.index', ['patient' => $visit->patient_id]),
        ])->all();

        $patientData = null;
        $consultation = null;

        if ($selectedVisit) {
            $patient = $selectedVisit->patient;

            $consultation = $selectedVisit->consultations->firstWhere('status', Consultation::STATUS_IN_PROGRESS)
                ?? $selectedVisit->consultations()->create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'status' => Consultation::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ]);

            $vitalLabels = self::DEFAULT_VITALS[$selectedVisit->orientation] ?? self::DEFAULT_VITALS['Médecin généraliste'];
            $vitals = collect($consultation->vitals ?: collect($vitalLabels)->map(fn (string $label) => ['label' => $label, 'value' => '']))
                ->values()->all();

            $patientData = [
                'record_id' => $patient->id,
                'name' => $patient->full_name,
                'initials' => $patient->initials,
                'id' => $patient->code,
                'visit_id' => $selectedVisit->id,
                'age' => $patient->birth_date?->age,
                'sex' => $patient->sex,
                'allergy' => $patient->allergies ?: 'Aucune connue',
                'coverage' => $patient->coverage,
                'guardian_name' => $patient->guardian_name,
                'emergency_contact' => $patient->emergency_contact,
                'vitals' => $vitals,
                'referrals' => Referral::where('patient_id', $patient->id)->latest('requested_at')->get(),
            ];
        }

        $serviceOptions = [
            'Optique' => 'Optique',
            'ORL' => 'ORL',
            'Imagerie médicale' => 'Imagerie médicale',
            'Radiologie' => 'Radiologie',
            'Cardiologie' => 'Cardiologie',
            'Dermatologie' => 'Dermatologie',
            'Autre' => 'Autre service',
        ];

        return view('medecin.dashboard', [
            'queue' => $queue,
            'patient' => $patientData,
            'consultation' => $consultation,
            'timeline' => $selectedVisit ? PatientTimeline::build($selectedVisit->patient) : [],
            'serviceOptions' => $serviceOptions,
            'medications' => Medication::orderBy('name')->get(),
            'isOnCallToday' => $doctor->isDoctor() && $doctor->isOnCallToday(),
            'userName' => $doctor->name,
            'userRole' => $doctor->role->label(),
            'userInitials' => $this->initialsFor($doctor->name),
        ]);
    }
}
