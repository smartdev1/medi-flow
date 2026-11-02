<?php

use App\Models\LabExam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonne de recherche normalisée pour le catalogue des examens.
 *
 * Comparer « hep » à « hépatique » en empilant des REPLACE en SQL overflow le
 * parseur de SQLite et annule l'index sur `name`. Une colonne dédiée, sans
 * accents ni majuscules, rend la recherche à la fois insensible aux accents et
 * indexable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_exams', function (Blueprint $table) {
            $table->string('search_index')->nullable()->after('category')->index();
        });

        // Le catalogue se compte par dizaines de lignes : un recalcul en PHP
        // reste plus simple et plus portable qu'une expression SQL.
        LabExam::query()->each(function (LabExam $exam) {
            $exam->forceFill(['search_index' => LabExam::buildSearchIndex($exam->name, $exam->category)])
                ->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('lab_exams', function (Blueprint $table) {
            $table->dropColumn('search_index');
        });
    }
};
