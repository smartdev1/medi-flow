<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un examen est la conséquence d'une consultation : il appartient à la
     * visite qui l'a demandé, pas seulement au patient. Sans ce lien, le
     * recalcul d'une facture additionnait les examens de toutes les autres
     * visites du patient.
     *
     * La colonne reste nullable : une demande saisie directement au
     * laboratoire, hors consultation, n'a pas de visite de rattachement et
     * continue d'être imputée à la venue en cours.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('exams', 'visit_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->foreignId('visit_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        $this->backfillVisitId();
    }

    /**
     * Rattache les examens enregistrés avant l'existence de la colonne à la
     * visite qui était ouverte à la date de la demande. Il s'agit d'une
     * déduction par dates, pas d'une donnée saisie : les examens sans visite
     * identifiable restent non rattachés.
     */
    private function backfillVisitId(): void
    {
        DB::table('exams')
            ->whereNull('visit_id')
            ->orderBy('id')
            ->each(function (object $exam) {
                $visit = DB::table('visits')
                    ->where('patient_id', $exam->patient_id)
                    ->where('arrived_at', '<=', $exam->requested_at)
                    ->where(function ($query) use ($exam) {
                        $query->whereNull('discharged_at')
                            ->orWhere('discharged_at', '>=', $exam->requested_at);
                    })
                    ->orderByDesc('arrived_at')
                    ->first();

                if ($visit) {
                    DB::table('exams')
                        ->where('id', $exam->id)
                        ->update(['visit_id' => $visit->id]);
                }
            });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite ne sait pas retirer une clé étrangère seule ; il réécrit la
        // table lors du dropColumn, ce qui suffit.
        if ($driver !== 'sqlite' && Schema::hasColumn('exams', 'visit_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropForeign(['visit_id']);
            });
        }

        if (Schema::hasColumn('exams', 'visit_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropColumn('visit_id');
            });
        }
    }
};