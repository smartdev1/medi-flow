<?php

namespace App\Support;

use App\Models\Consultation;
use App\Models\Exam;
use App\Models\Visit;
use Illuminate\Support\Collection;

class DoctorQueue
{
    /**
     * Construit la file d'attente du médecin.
     *
     * Source unique de vérité partagée par `MedecinController@index`, son
     * endpoint `refresh` et le badge de navigation : un patient qui a quitté la
     * file ne doit pas rester affiché ailleurs.
     *
     * Un patient sort de la file dès qu'il n'est plus au médecin : consultation
     * clôturée, hospitalisé, ou orienté vers le laboratoire. Les deux sens sont
     * automatiques — un examen demandé le fait sortir, et le résultat prêt le
     * fait revenir, sans intervention du médecin.
     */
    public static function visits(): Collection
    {
        return Visit::with(['patient', 'consultations', 'patient.admissions', 'patient.exams'])
            ->active()
            ->whereNotNull('vitals_taken_at')
            ->orderBy('arrived_at')
            ->get()
            ->reject(function (Visit $visit) {
                $latest = $visit->consultations->sortByDesc('started_at')->first();

                return $latest && $latest->status === Consultation::STATUS_CLOSED;
            })
            ->reject(fn (Visit $visit) => $visit->patient->admissions->contains(
                fn ($admission) => $admission->discharged_at === null
            ))
            ->reject(fn (Visit $visit) => $visit->patient->exams->contains(
                fn (Exam $exam) => in_array($exam->status, Exam::PENDING_IN_LABORATORY, true)
            ))
            ->values();
    }

    /**
     * Nombre de patients actuellement dans la file du médecin.
     */
    public static function count(): int
    {
        return self::visits()->count();
    }
}
