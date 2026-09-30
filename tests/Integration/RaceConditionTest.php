<?php

namespace Tests\Integration;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RaceConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_admissions_cannot_occupy_same_bed(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $room = Room::factory()->create(['capacity' => 1]);
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        // First admission succeeds
        $response1 = $this->actingAs($user)
            ->post(route('admissions.store'), [
                'patient_id' => $patient1->id,
                'room_id' => $room->id,
                'reason' => 'Test 1',
                'attending' => 'Dr Test',
            ]);

        $response1->assertSessionHasNoErrors();

        // Second admission on the same room fails (only 1 bed)
        $response2 = $this->actingAs($user)
            ->post(route('admissions.store'), [
                'patient_id' => $patient2->id,
                'room_id' => $room->id,
                'reason' => 'Test 2',
                'attending' => 'Dr Test',
            ]);

        $response2->assertSessionHasErrors('room_id');

        // Only one admission exists
        $this->assertSame(1, Admission::count());
    }

    public function test_approve_request_prevents_double_bed_assignment(): void
    {
        $aide = User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2]);

        $request1 = AdmissionRequest::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'requested_by' => $aide->id,
            'type' => 'hospitalisation',
            'reason' => 'Test',
            'status' => \App\Models\AdmissionRequest::STATUS_PENDING,
        ]);

        // Approve first request
        $response = $this->actingAs($aide)
            ->post(route('admission-requests.approve', $request1), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ]);

        $response->assertSessionHasNoErrors();

        // Try to approve a second request for the same room/bed
        $patient2 = Patient::factory()->create();
        $visit2 = Visit::factory()->create(['patient_id' => $patient2->id]);
        $request2 = AdmissionRequest::create([
            'patient_id' => $patient2->id,
            'visit_id' => $visit2->id,
            'requested_by' => $aide->id,
            'type' => 'hospitalisation',
            'reason' => 'Test 2',
            'status' => \App\Models\AdmissionRequest::STATUS_PENDING,
        ]);

        $response2 = $this->actingAs($aide)
            ->post(route('admission-requests.approve', $request2), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ]);

        $response2->assertSessionHasErrors();
    }

    public function test_patient_registration_is_atomic(): void
    {
        $accueil = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $act = Act::factory()->create();

        $response = $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Atomic',
                'last_name' => 'Patient',
                'sex' => 'M',
                'coverage' => 'Non assuré',
                'act_id' => $act->id,
            ]);

        $response->assertRedirect(route('accueil.index'));

        // All three records must exist
        $patient = Patient::where('first_name', 'Atomic')->first();
        $this->assertNotNull($patient);
        $this->assertDatabaseHas('visits', ['patient_id' => $patient->id]);
        $this->assertDatabaseHas('invoices', ['patient_id' => $patient->id]);
    }
}
