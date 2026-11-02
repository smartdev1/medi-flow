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
            ->post(route('insurance.store'), ['name' => 'SICAM Admin']);

        $response->assertRedirect(route('insurance.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('insurance_companies', ['name' => 'SICAM Admin']);
    }

    public function test_an_insurance_company_still_requires_a_name(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('insurance.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('insurance_companies', 0);
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
                'ticket_moderateur' => 80,
            ]);

        $response->assertRedirect(route('accueil.index'));

        $patient = Patient::latest('id')->first();
        $this->assertNotNull($patient);
        $this->assertEquals($company->id, $patient->insurance_company_id);
    }
}
