<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Montant du séjour convenu, saisi par la caisse : le ticket
            // modérateur ne s'applique pas à l'hospitalisation ni à
            // l'observation, leur prix n'est donc pas un pourcentage d'acte.
            if (! Schema::hasColumn('invoices', 'agreed_stay_amount')) {
                $table->integer('agreed_stay_amount')
                    ->nullable()
                    ->after('breakdown');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            // Rattachement de l'encaissement à la facture qu'il solde : sans ce
            // lien, le rapprochement encaissement / dette n'est pas possible.
            if (! Schema::hasColumn('payments', 'invoice_id')) {
                $table->foreignId('invoice_id')
                    ->nullable()
                    ->after('visit_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });

        // Clôture de journée dépréciée, remplacée par CashierSession.
        // Sous MySQL, la clé étrangère doit tomber avant la colonne qu'elle
        // référence. SQLite ne sait pas retirer une clé seule et réécrit la
        // table lors d'un dropColumn : l'appeler y échouerait.
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite' && Schema::hasColumn('payments', 'closed_by')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['closed_by']);
            });
        }

        // MySQL supprime l'index avec la colonne ; SQLite le laisse orphelin et
        // refuse ensuite toute écriture sur la table. On le retire donc nous-mêmes.
        if ($driver === 'sqlite' && Schema::hasColumn('payments', 'closed_at')) {
            $indexes = Schema::getConnection()->select("PRAGMA index_list('payments')");
            $names = array_column($indexes, 'name');

            if (in_array('payments_closed_at_index', $names, true)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropIndex('payments_closed_at_index');
                });
            }
        }

        Schema::table('payments', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['closed_at', 'closed_by'],
                fn (string $column) => Schema::hasColumn('payments', $column)
            ));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable();
            $table->dropConstrainedForeignId('invoice_id');
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('agreed_stay_amount');
        });
    }
};
