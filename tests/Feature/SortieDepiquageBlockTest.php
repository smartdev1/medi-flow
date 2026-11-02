<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortieDepiquageBlockTest extends TestCase
{
    use RefreshDatabase;

    private function receptionUser(): User
    {
        return User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);
    }

    private function doctorUser(): User
    {
        return User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Create a patient with a visit whose admission has been medically discharged
     * but depiquage has NOT been validated.
     */
    private function admissionWithoutDepiquage(string $admissionStatus = Admission::STATUS_STABLE): array
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => $admissionStatus,
            'admitted_at' => now()->subDays(2),
            'discharged_at' => now()->subHours(3),
            'depiquage_valide_par' => null,
            'depiquage_valide_le' => null,
        ]);

        return [$patient, $visit, $admission];
    }

    /**
     * Create a patient with a visit whose admission has been medically discharged
     * AND depiquage has been validated.
     */
    private function admissionWithDepiquage(string $admissionStatus = Admission::STATUS_STABLE): array
    {
        [$patient, $visit, $admission] = $this->admissionWithoutDepiquage($admissionStatus);

        $aide = User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);

        $admission->update([
            'depiquage_valide_par' => $aide->id,
            'depiquage_valide_le' => now(),
        ]);

        return [$patient, $visit, $admission];
    }

    // ─── AC1: close() rejects when depiquage not validated ───────────────────

    public function test_close_rejects_when_depiquage_not_validated_hospitalisation(): void
    {
        [$patient, $visit] = $this->admissionWithoutDepiquage(Admission::STATUS_STABLE);

        $response = $this->actingAs($this->receptionUser())
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('error', 'Clôture impossible : le dépiquage du patient n\'a pas été validé par les aides-soignantes.');

        $visit->refresh();
        $this->assertNull($visit->discharged_at);
    }

    public function test_close_rejects_when_depiquage_not_validated_observation(): void
    {
        [$patient, $visit] = $this->admissionWithoutDepiquage(Admission::STATUS_OBSERVATION);

        $response = $this->actingAs($this->receptionUser())
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('error', 'Clôture impossible : le dépiquage du patient n\'a pas été validé par les aides-soignantes.');

        $visit->refresh();
        $this->assertNull($visit->discharged_at);
    }

    // ─── AC1 continued: close() allows when depiquage validated ──────────────

    public function test_close_allows_when_depiquage_validated(): void
    {
        [$patient, $visit] = $this->admissionWithDepiquage(Admission::STATUS_STABLE);

        $response = $this->actingAs($this->receptionUser())
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }

    // ─── AC1 continued: close() allows consultation-only patients ────────────

    public function test_close_allows_for_consultation_only_patients(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        $response = $this->actingAs($this->receptionUser())
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }

    // ─── AC2: isReadyForClosure() ────────────────────────────────────────────

    public function test_isReadyForClosure_excludes_patients_without_depiquage(): void
    {
        [$patient, $visit] = $this->admissionWithoutDepiquage(Admission::STATUS_STABLE);

        $controller = app(\App\Http\Controllers\SortieController::class);
        $method = new \ReflectionMethod($controller, 'isReadyForClosure');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, $visit));
    }

    public function test_isReadyForClosure_includes_patients_with_depiquage(): void
    {
        [$patient, $visit] = $this->admissionWithDepiquage(Admission::STATUS_STABLE);

        $controller = app(\App\Http\Controllers\SortieController::class);
        $method = new \ReflectionMethod($controller, 'isReadyForClosure');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $visit));
    }

    public function test_isReadyForClosure_includes_consultation_only_patients(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        $controller = app(\App\Http\Controllers\SortieController::class);
        $method = new \ReflectionMethod($controller, 'isReadyForClosure');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $visit));
    }
}
