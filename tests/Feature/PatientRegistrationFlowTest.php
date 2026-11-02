<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Act;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_accueil_can_register_new_patient(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $act = Act::factory()->create();

        $response = $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Marie',
                'last_name' => 'Kouassi',
                'sex' => 'F',
                'coverage' => 'Non assuré',
                'act_id' => $act->id,
            ]);

        $response->assertRedirect(route('accueil.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Marie',
            'last_name' => 'Kouassi',
        ]);

        $patient = Patient::where('first_name', 'Marie')->where('last_name', 'Kouassi')->first();
        $this->assertNotNull($patient);
        $this->assertNotNull($patient->code);
        $this->assertStringStartsWith('CSM-', $patient->code);

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_WAITING,
        ]);

        $this->assertDatabaseHas('invoices', [
            'patient_id' => $patient->id,
            'status' => Invoice::STATUS_PENDING,
        ]);
    }

    public function test_accueil_can_register_returning_patient(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $existingPatient = Patient::factory()->create();

        $act = Act::factory()->create();

        $response = $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'existing_patient_id' => $existingPatient->id,
                'coverage' => 'Assuré',
                'act_id' => $act->id,
                'ticket_moderateur' => 80,
            ]);

        $response->assertRedirect(route('accueil.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'patient_id' => $existingPatient->id,
            'status' => Visit::STATUS_WAITING,
        ]);
    }

    public function test_an_insured_registration_requires_a_moderator_ticket(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $act = Act::factory()->create();

        // Le taux n'étant plus porté par l'assurance, une visite assurée sans
        // ticket saisi ferait facturer la totalité des actes au patient.
        $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Koffi',
                'last_name' => 'Amani',
                'sex' => 'M',
                'coverage' => 'Assuré',
                'act_id' => $act->id,
            ])
            ->assertSessionHasErrors('ticket_moderateur');

        $this->assertDatabaseMissing('patients', ['last_name' => 'Amani']);
    }

    public function test_an_uninsured_registration_does_not_require_a_moderator_ticket(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $act = Act::factory()->create();

        $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Yao',
                'last_name' => 'NGuessan',
                'sex' => 'M',
                'coverage' => 'Non assuré',
                'act_id' => $act->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('patients', ['last_name' => 'NGuessan']);
    }

    public function test_new_patient_visit_is_urgent_when_requested(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $act = Act::factory()->create();

        $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Urgent',
                'last_name' => 'Patient',
                'sex' => 'M',
                'coverage' => 'Non assuré',
                'urgent' => '1',
                'act_id' => $act->id,
            ]);

        $patient = Patient::where('first_name', 'Urgent')->first();
        $this->assertNotNull($patient);

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_URGENT,
        ]);
    }
}
