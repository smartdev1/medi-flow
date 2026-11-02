<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Patient::class);
        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        $patients = Patient::with(['insuranceCompany', 'visits' => fn ($q) => $q->latest('arrived_at')->limit(1)])
            ->orderByDesc('created_at')
            ->get();

        return view('patients.index', [
            'patients' => $patients,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $this->authorize('create', Patient::class);
        $data = $request->validated();
        $urgent = (bool) ($data['urgent'] ?? false);
        $isReturningPatient = ! empty($data['existing_patient_id']);

        $patient = $isReturningPatient
            ? Patient::findOrFail($data['existing_patient_id'])
            : Patient::create([
                'code' => Patient::generateCode(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'sex' => $data['sex'],
                'birth_date' => $data['birth_date'] ?? null,
                'phone' => $data['phone'] ?? null,
                'coverage' => $data['coverage'],
                'insurance_company_id' => $data['insurance_company_id'] ?? null,
            ]);

        $visit = $patient->visits()->create([
            'arrived_at' => now(),
            'reason' => $data['reason'] ?? null,
            'act_id' => $data['act_id'] ?? null,
            'registered_by' => Auth::id(),
            'status' => $urgent ? Visit::STATUS_URGENT : Visit::STATUS_WAITING,
            'urgent' => $urgent,
            'ticket_moderateur' => $data['ticket_moderateur']
                ?? (float) config('clinic.default_ticket_rate', 20),
            'surgery_type' => $data['surgery_type'] ?? null,
        ]);

        // Toute visite ouvre une facture : c'est le panier qui recevra ensuite
        // les examens, les ordonnances et les journées d'hospitalisation.
        Invoice::forVisit($visit);

        $message = $isReturningPatient
            ? "Nouvelle visite enregistrée pour {$patient->full_name} ({$patient->code})."
            : "Patient {$patient->full_name} enregistré — identifiant {$patient->code}.";

        return redirect()->route('accueil.index')->with('success', $message);
    }

    public function show(Patient $patient)
    {
        $this->authorize('view', $patient);
        // Charger toutes les données historiques du patient
        $patient->load([
            'visits.consultations',
            'admissions.room',
            'exams',
            'prescriptions.items.medication',
            'payments',
            'referrals'
        ]);

        // Organiser les événements par date (du plus récent au plus ancien)
        $timeline = collect();

        // Ajouter les visites et consultations
        foreach ($patient->visits as $visit) {
            if ($visit->arrived_at) {
                $timeline->push([
                    'date' => $visit->arrived_at,
                    'type' => 'visit',
                    'title' => 'Visite - ' . ($visit->reason ?: 'Consultation'),
                    'details' => $visit,
                    'icon' => 'calendar'
                ]);
            }

            foreach ($visit->consultations as $consultation) {
                if ($consultation->started_at) {
                    $timeline->push([
                        'date' => $consultation->started_at,
                        'type' => 'consultation',
                        'title' => 'Consultation avec ' . ($consultation->doctor->name ?? 'médecin'),
                        'details' => $consultation,
                        'icon' => 'stethoscope'
                    ]);
                }
            }
        }

        // Ajouter les hospitalisations
        foreach ($patient->admissions as $admission) {
            if ($admission->admitted_at) {
                $timeline->push([
                    'date' => $admission->admitted_at,
                    'type' => 'admission',
                    'title' => 'Hospitalisation - ' . $admission->reason,
                    'details' => $admission,
                    'icon' => 'bed'
                ]);
            }
        }

        // Ajouter les examens
        foreach ($patient->exams as $exam) {
            if ($exam->requested_at) {
                $timeline->push([
                    'date' => $exam->requested_at,
                    'type' => 'exam',
                    'title' => 'Examen - ' . $exam->type,
                    'details' => $exam,
                    'icon' => 'microscope'
                ]);
            }
        }

        // Ajouter les prescriptions
        foreach ($patient->prescriptions as $prescription) {
            if ($prescription->prescribed_at) {
                $timeline->push([
                    'date' => $prescription->prescribed_at,
                    'type' => 'prescription',
                    'title' => 'Ordonnance - ' . count($prescription->items) . ' médicament(s)',
                    'details' => $prescription,
                    'icon' => 'prescription'
                ]);
            }
        }

        // Ajouter les paiements
        foreach ($patient->payments as $payment) {
            if ($payment->paid_at) {
                $timeline->push([
                    'date' => $payment->paid_at,
                    'type' => 'payment',
                    'title' => 'Paiement - ' . $payment->amount . ' ' . $payment->currency,
                    'details' => $payment,
                    'icon' => 'credit-card'
                ]);
            }
        }

        // Trier par date (du plus récent au plus ancien)
        $timeline = $timeline->sortByDesc('date')->values();

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('patients.show', [
            'patient' => $patient,
            'timeline' => $timeline,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }
}
