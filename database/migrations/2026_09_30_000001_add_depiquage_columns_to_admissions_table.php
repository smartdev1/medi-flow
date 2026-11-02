<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->foreignId('depiquage_valide_par')
                ->nullable()
                ->after('discharged_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->datetime('depiquage_valide_le')
                ->nullable()
                ->after('depiquage_valide_par');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depiquage_valide_par');
            $table->dropColumn(['depiquage_valide_le']);
        });
    }
};
