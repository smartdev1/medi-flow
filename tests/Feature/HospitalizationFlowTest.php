<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use App\Models\User;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalizationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_request_observation(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($doctor)
            ->post(route('medecin.visits.observe', $visit));

        $response->assertRedirect(route('medecin.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_OBSERVATION_REQUESTED,
        ]);

        $this->assertDatabaseHas('admission_requests', [
            'visit_id' => $visit->id,
            'type' => 'observation',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);
    }

    public function test_aide_soignante_can_approve_observation_request(): void
    {
        $aide = User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $request = AdmissionRequest::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'requested_by' => $aide->id,
            'type' => 'observation',
            'reason' => 'Surveillance',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        $room = Room::factory()->create(['capacity' => 2]);

        $response = $this->actingAs($aide)
            ->post(route('admission-requests.approve', $request), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ]);

        $response->assertRedirect(route('admission-requests.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_OBSERVATION,
        ]);

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'status' => Admission::STATUS_OBSERVATION,
        ]);
    }

    public function test_aide_soignante_can_reject_request(): void
    {
        $aide = User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $request = AdmissionRequest::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'requested_by' => $aide->id,
            'type' => 'observation',
            'reason' => 'Surveillance',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($aide)
            ->post(route('admission-requests.reject', $request));

        $response->assertRedirect(route('admission-requests.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => Visit::STATUS_IN_CONSULTATION,
        ]);
    }

    public function test_admission_controller_prevents_double_bed_assignment(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $room = Room::factory()->create(['capacity' => 1]);

        // First admission fills the only bed
        $this->actingAs($user)
            ->post(route('admissions.store'), [
                'patient_id' => $patient->id,
                'room_id' => $room->id,
                'reason' => 'Test',
                'attending' => 'Dr Test',
            ]);

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'bed_number' => 1,
        ]);

        // Second admission on same room should fail
        $patient2 = Patient::factory()->create();
        $response = $this->actingAs($user)
            ->post(route('admissions.store'), [
                'patient_id' => $patient2->id,
                'room_id' => $room->id,
                'reason' => 'Test 2',
                'attending' => 'Dr Test',
            ]);

        $response->assertSessionHasErrors();
    }
}
