<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

/**
 * Données minimales et déterministes pour les tests de bout en bout.
 *
 * Rien n'est tiré au hasard : une sélection aléatoire — comme la catégorie d'un
 * acte dans `ActFactory` — rendrait un échec impossible à relire, puisque la
 * donnée de référence changerait d'une exécution à l'autre.
 */
class E2ESeeder extends Seeder
{
    /** Mot de passe commun à tous les comptes de test. */
    public const PASSWORD = 'password';

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        // Un patient en consultation, constantes prises : c'est l'état dans
        // lequel le médecin peut demander des examens.
        $patient = Patient::create([
            'first_name' => 'Aya',
            'last_name' => 'Kouassi',
            'sex' => 'F',
            'code' => 'PAT-E2E-0001',
            'coverage' => 'Non assuré',
        ]);

        Visit::create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_IN_CONSULTATION,
            'reason' => 'Fièvre et céphalées',
            'arrived_at' => now()->subMinutes(20),
            'vitals_taken_at' => now()->subMinutes(15),
        ]);
    }

    /**
     * Compte de test d'un rôle donné.
     */
    public static function accountFor(Role $role): User
    {
        return User::where('role', $role->value)->firstOrFail();
    }
}