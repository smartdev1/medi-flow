<?php

namespace App\Http\Controllers;

use App\Models\Act;
use App\Models\AdmissionRequest;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Referral;
use App\Models\Visit;
use App\Support\PatientTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MedecinController extends Controller
{

    public function index(Request $request): View
    {
        /** @var \App\Models\User $doctor */
        $doctor = Auth::user();

        $visits = Visit::with(['patient', 'consultations', 'patient.admissions'])
            ->active()
            ->whereNotNull('vitals_taken_at')
            ->orderBy('arrived_at')
            ->get()
            ->reject(function (Visit $visit) {
                $latest = $visit->consultations->sortByDesc('started_at')->first();

                return $latest && $latest->status === Consultation::STATUS_CLOSED;
            })
            ->reject(function (Visit $visit) {
                return $visit->patient->admissions()->whereNull('discharged_at')->exists();
            })
            ->values();

        $selectedVisitId = $request->integer('patient');
        $selectedVisit = $selectedVisitId
            ? $visits->firstWhere('id', $selectedVisitId)
            : null;

        $queue = $visits->map(fn (Visit $visit) => [
            'name' => $visit->patient->full_name,
            'initials' => $visit->patient->initials,
            'time' => $visit->arrived_at->diffForHumans(null, true).' d\'attente',
            'meta' => $visit->reason ?: 'Consultation',
            'urgent' => $visit->urgent,
            'href' => route('medecin.index', ['patient' => $visit->id]),
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
            'acts' => Act::orderBy('category')->orderBy('label')->get(),
            'currentActId' => $selectedVisit?->act_id,
            'isOnCallToday' => $doctor->isDoctor() && $doctor->isOnCallToday(),
            'userName' => $doctor->name,
            'userRole' => $doctor->role->label(),
            'userInitials' => $this->initialsFor($doctor->name),
        ]);
    }

    public function takeVisit(Request $request, Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => Visit::STATUS_IN_CONSULTATION,
            'vitals_taken_at' => $visit->vitals_taken_at ?: now(),
        ]);

        $visit->consultations()->firstOrCreate(
            [
                'visit_id' => $visit->id,
                'status' => Consultation::STATUS_IN_PROGRESS,
            ],
            [
                'patient_id' => $visit->patient_id,
                'doctor_id' => Auth::id(),
                'started_at' => now(),
            ]
        );

        return redirect()->route('medecin.index', ['patient' => $visit->id])
            ->with('success', "Patient {$visit->patient->full_name} pris en consultation.");
    }

    public function requestHospitalization(Request $request, Visit $visit): RedirectResponse
    {
        $patient = $visit->patient;

        if ($patient->admissions()->whereNull('discharged_at')->exists()) {
            return redirect()->route('medecin.index', ['patient' => $visit->id])
                ->with('error', 'Ce patient est déjà hospitalisé.');
        }

        $this->guardDuplicateRequest($visit);

        $visit->update(['status' => Visit::STATUS_HOSPITALIZATION_REQUESTED]);

        AdmissionRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'requested_by' => Auth::id(),
            'type' => 'hospitalisation',
            'reason' => $request->input('reason', $visit->reason ?: 'Hospitalisation demandée'),
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        return redirect()->route('medecin.index')
            ->with('success', "Demande d'hospitalisation enregistrée pour {$patient->full_name} — en attente de validation.");
    }

    public function requestObservation(Request $request, Visit $visit): RedirectResponse
    {
        $patient = $visit->patient;

        if ($patient->admissions()->whereNull('discharged_at')->exists()) {
            return redirect()->route('medecin.index', ['patient' => $visit->id])
                ->with('error', 'Ce patient est déjà en observation ou hospitalisé.');
        }

        $this->guardDuplicateRequest($visit);

        $visit->update(['status' => Visit::STATUS_OBSERVATION_REQUESTED]);

        AdmissionRequest::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'requested_by' => Auth::id(),
            'type' => 'observation',
            'reason' => $request->input('reason', $visit->reason ?: 'Mise en observation demandée'),
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        return redirect()->route('medecin.index')
            ->with('success', "Demande de mise en observation enregistrée pour {$patient->full_name} — en attente de validation.");
    }

    /**
     * Empêche d'empiler plusieurs demandes d'admission sur une même visite :
     * une seule demande en attente doit exister à la fois.
     */
    private function guardDuplicateRequest(Visit $visit): void
    {
        $existing = AdmissionRequest::where('visit_id', $visit->id)
            ->where('status', AdmissionRequest::STATUS_PENDING)
            ->exists();

        abort_if($existing, 422, 'Une demande d\'admission est déjà en attente pour cette visite.');
    }

    public function refresh(): \Illuminate\Http\JsonResponse
    {
        $visits = Visit::with(['patient', 'consultations', 'patient.admissions'])
            ->active()
            ->whereNotNull('vitals_taken_at')
            ->orderBy('arrived_at')
            ->get()
            ->reject(function (Visit $visit) {
                $latest = $visit->consultations->sortByDesc('started_at')->first();

                return $latest && $latest->status === \App\Models\Consultation::STATUS_CLOSED;
            })
            ->reject(function (Visit $visit) {
                return $visit->patient->admissions()->whereNull('discharged_at')->exists();
            })
            ->values();

        $queue = $visits->map(fn (Visit $visit) => [
            'name' => $visit->patient->full_name,
            'initials' => $visit->patient->initials,
            'time' => $visit->arrived_at->diffForHumans(null, true).' d\'attente',
            'meta' => $visit->reason ?: 'Consultation',
            'urgent' => $visit->urgent,
            'href' => route('medecin.index', ['patient' => $visit->id]),
        ])->all();

        return response()->json(['queue' => $queue]);
    }
}
