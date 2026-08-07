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
            ['name' => 'Akissi Koffi', 'email' => 'akissi.koffi@clinique-saintmatthieu.ci', 'role' => Role::Accueil],
            ['name' => 'Yao Kacou', 'email' => 'yao.kacou@clinique-saintmatthieu.ci', 'role' => Role::Caissier],
            ['name' => 'Adama Traoré', 'email' => 'adama.traore@clinique-saintmatthieu.ci', 'role' => Role::Medecin],
            ['name' => 'Fatoumata Sanogo', 'email' => 'fatoumata.sanogo@clinique-saintmatthieu.ci', 'role' => Role::Pediatre],
            ['name' => 'Brou Kouassi', 'email' => 'brou.kouassi@clinique-saintmatthieu.ci', 'role' => Role::Laborantin],
            ['name' => 'Aïcha Cissé', 'email' => 'aicha.cisse@clinique-saintmatthieu.ci', 'role' => Role::Pharmacien],
            ['name' => 'Moussa Bamba', 'email' => 'moussa.bamba@clinique-saintmatthieu.ci', 'role' => Role::Logistique],
            ['name' => 'Kouamé Bilé', 'email' => 'kouame.bile@clinique-saintmatthieu.ci', 'role' => Role::Admin],
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
