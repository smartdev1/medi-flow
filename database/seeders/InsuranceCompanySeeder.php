<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['name' => 'CNAM', 'rate' => 80],
            ['name' => 'MUGEFCI', 'rate' => 70],
            ['name' => 'ASCOMA', 'rate' => 60],
            ['name' => 'AXA Assurance', 'rate' => 50],
            ['name' => 'NSIA Assurance', 'rate' => 50],
            ['name' => 'SAHAM Assurance', 'rate' => 60],
            ['name' => 'COLAS', 'rate' => 70],
        ];

        foreach ($companies as $company) {
            InsuranceCompany::firstOrCreate(
                ['name' => $company['name']],
                ['rate' => $company['rate']]
            );
        }
    }
}
