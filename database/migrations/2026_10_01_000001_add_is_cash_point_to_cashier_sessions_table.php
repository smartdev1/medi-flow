<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue la vacation de caisse du point de vente pharmacie.
 *
 * Les deux portent des fonds et doivent être rapprochées, mais ce ne sont pas
 * les mêmes fonds : sans cette colonne, un relevé de caisse regroupe les deux
 * et le rapprochement devient faux sans aucun signe visible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->boolean('is_cash_point')->default(false)->after('cashier_id');
        });

        // La désignation du point de vente actif est une lecture fréquente et
        // filtrée : sans index, chaque vente pharmacie parcourt toutes les
        // vacations, y compris les anciennes et fermées.
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->index(['is_cash_point', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->dropIndex(['is_cash_point', 'closed_at']);
            $table->dropColumn('is_cash_point');
        });
    }
};
