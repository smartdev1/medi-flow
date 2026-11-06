<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $staff = [
            ['name' => 'Agent Accueil', 'email' => 'accueil@cmsm.ci', 'role' => Role::Accueil],
            ['name' => 'Caissier', 'email' => 'caisse@cmsm.ci', 'role' => Role::Caissier],
            ['name' => 'Médecin Généraliste', 'email' => 'medecin@cmsm.ci', 'role' => Role::Medecin],
            ['name' => 'Pédiatre', 'email' => 'pediatre@cmsm.ci', 'role' => Role::Pediatre],
            ['name' => 'Chirurgien', 'email' => 'chirurgien@cmsm.ci', 'role' => Role::Chirurgien],
            ['name' => 'Anesthésiste', 'email' => 'anesthesiste@cmsm.ci', 'role' => Role::Anesthesiste],
            ['name' => 'Cardiologue', 'email' => 'cardiologue@cmsm.ci', 'role' => Role::Cardiologue],
            ['name' => 'Dermatologue', 'email' => 'dermatologue@cmsm.ci', 'role' => Role::Dermatologue],
            ['name' => 'ORL', 'email' => 'orl@cmsm.ci', 'role' => Role::Orl],
            ['name' => 'Ophtalmologue', 'email' => 'ophtalmo@cmsm.ci', 'role' => Role::Ophtalmo],
            ['name' => 'Dentiste', 'email' => 'dentiste@cmsm.ci', 'role' => Role::Dentiste],
            ['name' => 'Laborantin', 'email' => 'labo@cmsm.ci', 'role' => Role::Laborantin],
            ['name' => 'Pharmacien', 'email' => 'pharmacie@cmsm.ci', 'role' => Role::Pharmacien],
            ['name' => 'Aide-Soignante', 'email' => 'aide@cmsm.ci', 'role' => Role::AideSoignante],
            ['name' => 'Agent Logistique', 'email' => 'logistique@cmsm.ci', 'role' => Role::Logistique],
            ['name' => 'Docteur Général', 'email' => 'dr_gnle@cmsm.ci', 'role' => Role::DocteurGeneral],
            ['name' => 'RAF', 'email' => 'raf@cmsm.ci', 'role' => Role::Raf],
            ['name' => 'Administrateur', 'email' => 'admin@cmsm.ci', 'role' => Role::Admin],
        ];

        foreach ($staff as $entry) {
            User::updateOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'role' => $entry['role'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->call(ActSeeder::class);
        $this->call(LabExamSeeder::class);
        $this->call(InsuranceCompanySeeder::class);
        $this->call(ClinicSeeder::class);
    }
}
