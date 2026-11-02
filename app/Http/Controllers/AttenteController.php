<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Exam;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttenteController extends Controller
{
    /**
     * Ordre d'affichage des colonnes du circuit, avec leur icône.
     */
    private const STAGES = [
        'Consultation' => 'stethoscope',
        'Hospitalisation' => 'building',
        'Mise en Observation' => 'eye',
        'Laboratoire' => 'beaker',
        'Retour médecin' => 'document',
        'Sortie' => 'check-circle',
    ];

    public function index(): View
    {
        $stages = $this->getStagesData();

        $canTakeVitals = auth()->user()?->role?->value === 'aide_soignante';

        $user = Auth::user();
        $userName = $user?->name ?? 'Réceptionniste';
        $userRole = $user?->role?->label() ?? 'Réception';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('attente.board', [
            'stages' => $stages,
            'canTakeVitals' => $canTakeVitals,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Rafraîchit les données du circuit patient (pour polling AJAX).
     */
    public function refresh(): JsonResponse
    {
        $stages = $this->getStagesData();

        return response()->json([
            'stages' => $stages,
            'canTakeVitals' => auth()->user()?->role?->value === 'aide_soignante',
        ]);
    }

    /**
     * Récupère les données structurées pour le circuit patient.
     * 
     * @return array
     */
    private function getStagesData(): array
    {
        $visits = Visit::with([
            'patient',
            'patient.admissions.room',
            'patient.exams',
            'vitalsTakenBy',
        ])->active()
            ->whereDate('arrived_at', today())
            ->orderBy('arrived_at')
            ->get();

        $cardsByStage = collect(array_keys(self::STAGES))->mapWithKeys(fn (string $stage) => [$stage => collect()]);

        foreach ($visits as $visit) {
            // Preload admissions/exams on the patient model for buildCard()
            if (! $visit->patient->relationLoaded('admissions')) {
                $visit->patient->load(['admissions.room', 'exams']);
            }

            $card = $this->buildCard($visit->patient, $visit);

            if ($card) {
                $cardsByStage[$card['stage']]->push($card);
            }
        }

        return collect(self::STAGES)->map(fn (string $icon, string $label) => [
            'label' => $label,
            'icon' => $icon,
            'patients' => $cardsByStage[$label]->values()->all(),
        ])->values()->all();
    }

    /**
     * Détermine où en est réellement ce patient dans le circuit, à partir de
     * ses vraies visites, paiements, examens, ordonnances et hospitalisations
     * — du stade le plus avancé (sortie) au moins avancé (accueil).
     */
    private function buildCard(Patient $patient, ?Visit $visit = null): ?array
    {
        if (!$visit) {
            return null;
        }

        $admission = $patient->admissions->whereNull('discharged_at')->first();
        if ($admission) {
            $tone = $visit?->urgent ? 'danger' : ($admission->isDischargePlanned() ? 'warning' : 'neutral');
            $badge = match (true) {
                (bool) $visit?->urgent => ['label' => 'Urgent', 'tone' => 'danger'],
                $admission->isDischargePlanned() => ['label' => 'Sortie prévue', 'tone' => 'warning'],
                default => null,
            };

            $stage = $admission->unit() === Admission::STATUS_OBSERVATION ? 'Mise en Observation' : 'Hospitalisation';
            return $this->card($patient, $stage, "{$admission->room->number} — {$admission->reason}", $tone, $badge, $visit);
        }

        if ($visit && str_starts_with($visit->status, 'Demande ')) {
            $stage = $visit->status === Visit::STATUS_OBSERVATION_REQUESTED ? 'Mise en Observation' : 'Hospitalisation';
            return $this->card($patient, $stage, $visit->reason ?: $stage, 'warning', ['label' => 'En attente validation', 'tone' => 'warning'], $visit);
        }

        $pendingExam = $patient->exams->first(fn (Exam $exam) => in_array($exam->status, Exam::PENDING_IN_LABORATORY, true));
        if ($pendingExam) {
            $tone = $pendingExam->status === Exam::STATUS_IN_PROGRESS ? 'brand' : 'neutral';

            return $this->card($patient, 'Laboratoire', "{$pendingExam->name} — ".lcfirst($pendingExam->status), $tone, badge: null, visit: $visit);
        }

        if ($visit) {
            if ($visit->status === Visit::STATUS_CONSULTATION_CLOSED) {
                return null;
            }

            $tone = $visit->urgent ? 'danger' : 'brand';
            $badge = $visit->urgent ? ['label' => 'Urgent', 'tone' => 'danger'] : null;

            return $this->card($patient, 'Consultation', $visit->reason ?: 'Consultation', $tone, $badge, $visit);
        }

        return null;
    }

    private function card(Patient $patient, string $stage, string $meta, string $tone, ?array $badge = null, ?Visit $visit = null): array
    {
        $activeVisit = $visit
            ?? $patient->visits->first(fn (Visit $v) => ! $v->isClosed());

        return [
            'stage' => $stage,
            'name' => $patient->full_name,
            'id' => $patient->code,
            'patient_id' => $patient->id,
            'visit_id' => $activeVisit?->id,
            'vitals_taken_at' => $activeVisit?->vitals_taken_at,
            'vitals_taken_by' => $activeVisit?->vitalsTakenBy?->name,
            'meta' => $meta,
            'tone' => $tone,
            'badge' => $badge,
        ];
    }

    public function markVitals(Visit $visit)
    {
        $visit->update([
            'vitals_taken_at' => now(),
            'vitals_taken_by' => Auth::id(),
        ]);

        return back()->with('success', "Constantes enregistrées pour {$visit->patient->full_name}.");
    }


}
