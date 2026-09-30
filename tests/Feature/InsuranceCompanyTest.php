<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_insurance_company(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('insurance.store'), [
                'name' => 'SICAM Admin',
                'rate' => 80,
            ]);

        $response->assertRedirect(route('insurance.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('insurance_companies', ['name' => 'SICAM Admin', 'rate' => 80]);
    }

    public function test_an_insurance_company_requires_a_coverage_rate(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        // Sans taux, la facture de chaque patient de cette assurance lui
        // ferait payer la totalité des actes.
        $this->actingAs($admin)
            ->post(route('insurance.store'), ['name' => 'SICAM Sans Taux'])
            ->assertSessionHasErrors('rate');

        $this->assertDatabaseMissing('insurance_companies', ['name' => 'SICAM Sans Taux']);
    }

    public function test_patient_registration_can_be_linked_to_an_insurance_company(): void
    {
        $company = InsuranceCompany::factory()->create(['name' => 'SICAM Patient']);
        $receptionist = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($receptionist)
            ->post(route('patients.store'), [
                'first_name' => 'Koffi',
                'last_name' => 'Amani',
                'sex' => 'M',
                'coverage' => 'Assuré',
                'insurance_company_id' => $company->id,
            ]);

        $response->assertRedirect(route('accueil.index'));

        $patient = Patient::latest('id')->first();
        $this->assertNotNull($patient);
        $this->assertEquals($company->id, $patient->insurance_company_id);
    }
}
