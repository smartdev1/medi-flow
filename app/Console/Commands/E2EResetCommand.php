<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Vide les tables sans rejouer les migrations.
 *
 * `migrate:fresh` prend une vingtaine de secondes, et surtout le serveur de
 * développement tourne pendant ce temps sur la même base SQLite : le
 * verrouillage en écriture s'y fait sentir, et l'attente qui en résulte fait
 * dépasser le délai des tests navigateur sans qu'aucune erreur ne soit visible.
 *
 * Les clés étrangères sont désactivées le temps du vidage : les contraintes
 * d'intégrité ne décrivent pas ici un ordre de suppression possible, et les
 * tables sont toutes réinitialisées.
 */
class E2EResetCommand extends Command
{
    protected $signature = 'e2e:reset';

    protected $description = 'Vide la base des tests de bout en bout';

    public function handle(): int
    {
        $database = (string) DB::connection()->getDatabaseName();

        // Cette commande ne doit vider que la base éphémère des tests. La base
        // de développement est hors de question : une garde explicite vaut
        // mieux qu'une confiance dans une variable d'environnement.
        if (! str_contains($database, 'e2e')) {
            $this->error("Refus de vider « {$database} » : ce n'est pas une base de test.");

            return self::FAILURE;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table'") as $table) {
            $name = $table->name;

            if (str_starts_with($name, 'sqlite_')) {
                continue;
            }

            DB::statement("DELETE FROM \"{$name}\"");
        }

        DB::statement('PRAGMA foreign_keys = ON');

        $this->call('db:seed', ['--class' => 'E2ESeeder', '--force' => true]);

        return self::SUCCESS;
    }
}
