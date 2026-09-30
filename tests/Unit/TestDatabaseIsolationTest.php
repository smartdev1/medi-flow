<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Garde-fou : la suite ne doit jamais s'exécuter sur la base de développement.
 *
 * `RefreshDatabase` fait un `migrate:fresh`, donc tout test qui l'utilise
 * supprime et recrée les tables. Sans `DB_CONNECTION=sqlite` et
 * `DB_DATABASE=:memory:` dans phpunit.xml, la configuration retombe sur le
 * MySQL de l'application (config/database.php) et la base de développement est
 * vidée à chaque exécution.
 *
 * Ce test n'utilise volontairement pas RefreshDatabase : il doit pouvoir
 * s'exécuter sans rien migrer.
 */
class TestDatabaseIsolationTest extends TestCase
{
    public function test_the_suite_runs_on_an_isolated_in_memory_database(): void
    {
        $this->assertSame('sqlite', config('database.default'), 'La suite doit utiliser SQLite.');
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_the_connection_really_is_in_memory_sqlite(): void
    {
        $this->assertSame('sqlite', \DB::connection()->getDriverName());
        $this->assertSame(':memory:', \DB::connection()->getDatabaseName());
    }
}
