<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'CNAM',
            'MUGEFCI',
            'ASCOMA',
            'AXA Assurance',
            'NSIA Assurance',
            'SAHAM Assurance',
            'COLAS',
        ];

        foreach ($companies as $name) {
            InsuranceCompany::firstOrCreate(['name' => $name]);
        }
    }
}
