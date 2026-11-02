<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le ticket modérateur décrit un contrat, pas une compagnie ni un dossier
 * patient : deux consultations du même patient peuvent relever de deux taux
 * différents (20 % puis 10 % deux semaines plus tard). Il est donc porté par
 * la venue, capté par l'accueil au moment de l'enregistrement.
 *
 * Le taux de la compagnie d'assurance disparaît : il était unique pour tous ses
 * assurés, donc incapable de représenter un contrat individuel.
 *
 * L'ordonnance suit le même mouvement — c'est un acte — et devient rattachée à
 * la venue qui l'a prescrite. Sans ce lien, une ordonnance de la seconde
 * consultation était facturée sur la facture de la première, avec le taux de
 * la première.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('visits', 'ticket_moderateur')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->decimal('ticket_moderateur', 5, 2)
                    ->default(config('clinic.default_ticket_rate', 20));
            });
        }

        if (! Schema::hasColumn('prescriptions', 'visit_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->foreignId('visit_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        // `rate` n'est indexée sur aucune autre colonne : un retrait direct
        // fonctionne sur les deux pilotes.
        if (Schema::hasColumn('insurance_companies', 'rate')) {
            Schema::table('insurance_companies', function (Blueprint $table) {
                $table->dropColumn('rate');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! Schema::hasColumn('insurance_companies', 'rate')) {
            Schema::table('insurance_companies', function (Blueprint $table) {
                $table->integer('rate')->default(0);
            });
        }

        if (Schema::hasColumn('prescriptions', 'visit_id')) {
            if ($driver !== 'sqlite') {
                Schema::table('prescriptions', function (Blueprint $table) {
                    $table->dropForeign(['visit_id']);
                });
            }

            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('visit_id');
            });
        }

        if (Schema::hasColumn('visits', 'ticket_moderateur')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('ticket_moderateur');
            });
        }
    }
};