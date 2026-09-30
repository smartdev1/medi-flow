<?php

namespace Tests\Feature;

use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientListFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_patients_can_be_filtered_by_search_and_insurance_status(): void
    {
        $user = User::factory()->create([
            'role' => 'accueil',
            'email_verified_at' => now(),
        ]);

        $insurance = InsuranceCompany::factory()->create(['name' => 'SICAM']);

        $withInsurance = Patient::create([
            'code' => 'CSM-1001',
            'first_name' => 'Alice',
            'last_name' => 'Dupont',
            'sex' => 'F',
            'phone' => '0123456789',
            'coverage' => 'Assuré',
            'insurance_company_id' => $insurance->id,
        ]);

        $withoutInsurance = Patient::create([
            'code' => 'CSM-1002',
            'first_name' => 'Bob',
            'last_name' => 'Martin',
            'sex' => 'M',
            'phone' => '0987654321',
            'coverage' => 'Non assuré',
        ]);

        $response = $this->actingAs($user)->get('/patients');

        $response->assertOk();
        $response->assertSee($withInsurance->full_name);
        $response->assertSee($withoutInsurance->full_name);
    }
}
