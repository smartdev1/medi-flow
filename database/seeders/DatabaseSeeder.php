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
            ['name' => 'Akissi Koffi', 'email' => 'akissi.koffi@cmsm.ci', 'role' => Role::Accueil],
            ['name' => 'Yao Kacou', 'email' => 'yao.kacou@cmsm.ci', 'role' => Role::Caissier],
            ['name' => 'Adama Traoré', 'email' => 'adama.traore@cmsm.ci', 'role' => Role::Medecin],
            ['name' => 'Fatoumata Sanogo', 'email' => 'fatoumata.sanogo@cmsm.ci', 'role' => Role::Pediatre],
            ['name' => 'Brou Kouassi', 'email' => 'brou.kouassi@cmsm.ci', 'role' => Role::Laborantin],
            ['name' => 'Aïcha Cissé', 'email' => 'aicha.cisse@cmsm.ci', 'role' => Role::Pharmacien],
            ['name' => 'Moussa Bamba', 'email' => 'moussa.bamba@cmsm.ci', 'role' => Role::Logistique],
            ['name' => 'Kouamé Bilé', 'email' => 'kouame.bile@cmsm.ci', 'role' => Role::Admin],
            ['name' => 'Docteur Général', 'email' => 'dr_gnle@cmsm.ci', 'role' => Role::DocteurGeneral],
            ['name' => 'Stéphanie Aubier', 'email' => 'aide@cmsm.ci', 'role' => Role::AideSoignante],
            
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

        $this->call(ClinicSeeder::class);
    }
}
