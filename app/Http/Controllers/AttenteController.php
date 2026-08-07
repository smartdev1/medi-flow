<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Exam;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\View\View;

class AttenteController extends Controller
{
    /**
     * Ordre d'affichage des colonnes du circuit, avec leur icône.
     */
    private const STAGES = [
        'Accueil' => 'user-plus',
        'Consultation' => 'stethoscope',
        'Hospitalisation' => 'building',
        'Laboratoire' => 'beaker',
        'Retour médecin' => 'document',
        'Pharmacie' => 'capsule',
        'Sortie' => 'check-circle',
    ];

    public function index(): View
    {
        $patients = Patient::with(['visits', 'admissions.room', 'prescriptions.items.medication', 'exams', 'payments'])->get();

        $cardsByStage = collect(array_keys(self::STAGES))->mapWithKeys(fn (string $stage) => [$stage => collect()]);

        foreach ($patients as $patient) {
            $card = $this->buildCard($patient);

            if ($card) {
                $cardsByStage[$card['stage']]->push($card);
            }
        }

        $stages = collect(self::STAGES)->map(fn (string $icon, string $label) => [
            'label' => $label,
            'icon' => $icon,
            'patients' => $cardsByStage[$label]->values()->all(),
        ])->values();

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('attente.board', [
            'stages' => $stages,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Détermine où en est réellement ce patient dans le circuit, à partir de
     * ses vraies visites, paiements, examens, ordonnances et hospitalisations
     * — du stade le plus avancé (sortie) au moins avancé (accueil).
     */
    private function buildCard(Patient $patient): ?array
    {
        // Vérifier d'abord si le patient a une visite active (non clôturée)
        $activeVisit = $patient->visits->first(fn (\App\Models\Visit $visit) => $visit->isClosed() === false);
        
        if (!$activeVisit) {
            // Si toutes les visites sont clôturées, ne pas afficher le patient dans le circuit
            return null;
        }
        
        // Utiliser la visite active pour déterminer l'étape actuelle
        $visit = $activeVisit;

        $admission = $patient->admissions->whereNull('discharged_at')->first();
        if ($admission) {
            $tone = $visit?->urgent ? 'danger' : ($admission->status === Admission::STATUS_DISCHARGE_PLANNED ? 'warning' : 'neutral');
            $badge = match (true) {
                (bool) $visit?->urgent => ['label' => 'Urgent', 'tone' => 'danger'],
                $admission->status === Admission::STATUS_DISCHARGE_PLANNED => ['label' => 'Sortie prévue', 'tone' => 'warning'],
                default => null,
            };

            return $this->card($patient, 'Hospitalisation', "{$admission->room->number} — {$admission->reason}", $tone, $badge);
        }

        if ($visit && $visit->orientation === 'Non orientée') {
            return $this->card($patient, 'Accueil', 'Arrivée '.$visit->arrived_at->format('H:i').' — non orientée', 'neutral');
        }

        $pendingPrescription = $patient->prescriptions->first(fn (Prescription $p) => $p->status !== Prescription::STATUS_DISPENSED);
        if ($pendingPrescription) {
            $shortage = ! $pendingPrescription->hasSufficientStock();
            $tone = $shortage ? 'warning' : 'neutral';
            $badge = $shortage ? ['label' => 'Rupture', 'tone' => 'warning'] : null;

            return $this->card($patient, 'Pharmacie', $pendingPrescription->medications_summary, $tone, $badge);
        }

        $readyExam = $patient->exams->firstWhere('status', Exam::STATUS_READY);
        if ($readyExam) {
            return $this->card($patient, 'Retour médecin', "Résultats reçus — {$readyExam->name}", 'success', ['label' => 'Prêt', 'tone' => 'success']);
        }

        $pendingExam = $patient->exams->first(fn (Exam $exam) => in_array($exam->status, [Exam::STATUS_AWAITING_SAMPLE, Exam::STATUS_IN_PROGRESS], true));
        if ($pendingExam) {
            $tone = $pendingExam->status === Exam::STATUS_IN_PROGRESS ? 'brand' : 'neutral';

            return $this->card($patient, 'Laboratoire', "{$pendingExam->name} — ".lcfirst($pendingExam->status), $tone);
        }

        $unpaidPayment = $patient->payments->first(fn ($payment) => $payment->status !== 'Payé');
        if ($unpaidPayment && $visit?->status !== 'En consultation') {
            return $this->card($patient, 'Accueil', "En attente de paiement — {$unpaidPayment->service}", 'warning');
        }

        if ($visit) {
            $tone = $visit->urgent ? 'danger' : 'brand';
            $badge = $visit->urgent ? ['label' => 'Urgent', 'tone' => 'danger'] : null;

            return $this->card($patient, 'Consultation', $visit->orientation, $tone, $badge);
        }

        return null;
    }

    private function card(Patient $patient, string $stage, string $meta, string $tone, ?array $badge = null): array
    {
        return [
            'stage' => $stage,
            'name' => $patient->full_name,
            'id' => $patient->code,
            'patient_id' => $patient->id,
            'meta' => $meta,
            'tone' => $tone,
            'badge' => $badge,
        ];
    }


}
