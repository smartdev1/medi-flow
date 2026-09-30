<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_take_visit_in_consultation(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->waiting()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($doctor)
            ->post(route('medecin.visits.take', $visit));

        $response->assertRedirect('/medecin?patient='.$visit->id);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_IN_CONSULTATION,
        ]);
    }

    public function test_doctor_can_close_consultation(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $consultation = Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'En cours',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($doctor)
            ->post(route('consultations.close', $consultation));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);
    }

    public function test_doctor_can_request_hospitalization(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($doctor)
            ->post(route('medecin.visits.hospitalize', $visit));

        $response->assertRedirect(route('medecin.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_HOSPITALIZATION_REQUESTED,
        ]);
    }

    public function test_saving_notes_keeps_the_vitals_taken_at_the_front_desk(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $vitals = [
            ['label' => 'Tension', 'value' => '12/8'],
            ['label' => 'Pouls', 'value' => '78'],
        ];

        $consultation = Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'vitals' => $vitals,
            'status' => 'En cours',
            'started_at' => now(),
        ]);

        // Le formulaire du médecin n'envoie plus de constantes : l'enregistrement
        // des notes ne doit pas effacer celles relevées à l'accueil.
        $response = $this->actingAs($doctor)
            ->from(route('medecin.index', ['patient' => $visit->id]))
            ->post(route('consultations.update', $consultation), [
                'notes' => 'Diagnostic posé.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('medecin.index', ['patient' => $visit->id]));

        $this->assertSame('Diagnostic posé.', $consultation->fresh()->notes);
        $this->assertSame($vitals, $consultation->fresh()->vitals);
    }

    public function test_vitals_can_still_be_replaced_when_they_are_submitted(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $consultation = Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'vitals' => [['label' => 'Pouls', 'value' => '60']],
            'status' => 'En cours',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($doctor)
            ->from(route('medecin.index', ['patient' => $visit->id]))
            ->post(route('consultations.update', $consultation), [
                'vitals' => ['Pouls' => '95'],
                'notes' => 'Contrôle.',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            [['label' => 'Pouls', 'value' => '95']],
            $consultation->fresh()->vitals
        );
    }
}
