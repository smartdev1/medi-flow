<?php

namespace Tests\Integration;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionDischargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_observation_discharge_returns_visit_to_returned_status(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create();

        $admission = Admission::factory()->observation()->create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
        ]);

        $this->actingAs($user)
            ->post(route('admissions.discharge', $admission));

        $admission->refresh();
        $visit->refresh();

        $this->assertNotNull($admission->discharged_at);
        $this->assertSame(Visit::STATUS_RETURNED_FROM_OBSERVATION, $visit->status);
    }

    public function test_regular_discharge_sets_discharged_at(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create();

        $admission = Admission::factory()->stable()->create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
        ]);

        $this->actingAs($user)
            ->post(route('admissions.discharge', $admission));

        $admission->refresh();

        $this->assertNotNull($admission->discharged_at);
    }

    public function test_bed_becomes_available_after_discharge(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 1]);

        $admission = Admission::factory()->stable()->create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'bed_number' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('admissions.discharge', $admission));

        $admission->refresh();

        $this->assertNotNull($admission->discharged_at);
        // After discharge, the admission is no longer active
        $this->assertDatabaseMissing('admissions', [
            'id' => $admission->id,
            'discharged_at' => null,
        ]);
    }
}
