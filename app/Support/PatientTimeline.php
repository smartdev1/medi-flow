<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\Exam;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Support\Carbon;

class PatientTimeline
{
    /**
     * Reconstruit la chronologie réelle d'un patient à partir de ses visites,
     * paiements, consultations, examens, ordonnances, hospitalisations et
     * orientations — au lieu d'une chronologie figée codée en dur.
     */
    public static function build(Patient $patient): array
    {
        $patient->loadMissing(['visits', 'payments', 'consultations.doctor', 'exams', 'prescriptions.items', 'admissions.room', 'referrals']);

        $steps = collect();

        foreach ($patient->visits as $visit) {
            $steps->push([
                'time' => $visit->arrived_at,
                'label' => 'Accueil',
                'meta' => $visit->reason ?: ($visit->registeredBy?->name ?? 'Enregistrement'),
                'icon' => 'user-plus',
                'status' => 'done',
            ]);

            if ($visit->discharged_at) {
                $steps->push([
                    'time' => $visit->discharged_at,
                    'label' => 'Sortie',
                    'meta' => 'Dossier clôturé',
                    'icon' => 'check-circle',
                    'status' => 'done',
                ]);
            }
        }

        foreach ($patient->payments as $payment) {
            $steps->push([
                'time' => $payment->created_at,
                'label' => 'Paiement',
                'meta' => "{$payment->service} — {$payment->status}",
                'icon' => 'credit-card',
                'status' => $payment->status === 'Payé' ? 'done' : 'current',
            ]);
        }

        foreach ($patient->consultations as $consultation) {
            $steps->push([
                'time' => $consultation->started_at,
                'label' => 'Consultation',
                'meta' => $consultation->doctor->name ?? 'Médecin',
                'icon' => 'stethoscope',
                'status' => $consultation->status === $consultation::STATUS_CLOSED ? 'done' : 'current',
            ]);
        }

        foreach ($patient->exams as $exam) {
            $steps->push([
                'time' => $exam->requested_at,
                'label' => 'Laboratoire',
                'meta' => "{$exam->name} — {$exam->status}",
                'icon' => 'beaker',
                'status' => $exam->status === Exam::STATUS_SENT ? 'done' : 'current',
            ]);
        }

        foreach ($patient->prescriptions as $prescription) {
            $steps->push([
                'time' => $prescription->requested_at,
                'label' => 'Pharmacie',
                'meta' => "{$prescription->medications_summary} — {$prescription->status}",
                'icon' => 'capsule',
                'status' => $prescription->status === Prescription::STATUS_DISPENSED ? 'done' : 'current',
            ]);
        }

        foreach ($patient->admissions as $admission) {
            $steps->push([
                'time' => $admission->admitted_at,
                'label' => 'Hospitalisation',
                'meta' => "{$admission->room->number} — {$admission->reason}",
                'icon' => 'building',
                'status' => $admission->discharged_at ? 'done' : 'current',
            ]);

            if ($admission->discharged_at) {
                $steps->push([
                    'time' => $admission->discharged_at,
                    'label' => 'Fin d\'hospitalisation',
                    'meta' => $admission->room->number,
                    'icon' => 'check-circle',
                    'status' => 'done',
                ]);
            }
        }

        foreach ($patient->referrals as $referral) {
            $steps->push([
                'time' => $referral->requested_at,
                'label' => 'Orientation — '.$referral->service,
                'meta' => $referral->reason,
                'icon' => 'arrow-right',
                'status' => 'done',
            ]);
        }

        return $steps
            ->sortBy(fn (array $step) => Carbon::parse($step['time']))
            ->map(fn (array $step) => [
                'label' => $step['label'],
                'time' => Carbon::parse($step['time'])->format('H:i'),
                'meta' => $step['meta'],
                'icon' => $step['icon'],
                'status' => $step['status'],
            ])
            ->values()
            ->all();
    }
}
