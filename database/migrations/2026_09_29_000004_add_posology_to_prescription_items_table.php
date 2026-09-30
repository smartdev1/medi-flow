<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posologie : « 1 comprimé matin et soir pendant 5 jours ».
     *
     * Elle fait partie de l'ordonnance prescriptrice. Sans elle, le pharmacien
     * délivre un médicament dont ni la dose ni la durée n'ont été transmises.
     * Facultative pour ne pas bloquer une prescription dont la posologie a été
     * donnée oralement, mais elle doit pouvoir être enregistrée.
     */
    public function up(): void
    {
        if (Schema::hasColumn('prescription_items', 'posology')) {
            return;
        }

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('posology')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['posology'],
                fn (string $column) => Schema::hasColumn('prescription_items', $column)
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
