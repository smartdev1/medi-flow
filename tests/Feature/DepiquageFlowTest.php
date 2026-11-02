<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepiquageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-06-15 10:00:00'));
    }

    private function aide(): User
    {
        return User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);
    }

    private function doctor(): User
    {
        return User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);
    }

    private function dischargedAdmission(): Admission
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        return $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now()->subDays(2),
            'discharged_at' => now()->subHours(3),
        ]);
    }

    // ─── Model methods ───────────────────────────────────────────────────────

    public function test_isDepiquageValide_returns_false_when_not_validated(): void
    {
        $admission = $this->dischargedAdmission();

        $this->assertFalse($admission->isDepiquageValide());
    }

    public function test_isDepiquageValide_returns_true_when_validated(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->assertTrue($admission->isDepiquageValide());
    }

    public function test_canBeDepiqued_returns_true_after_medical_discharge(): void
    {
        $admission = $this->dischargedAdmission();

        $this->assertTrue($admission->canBeDepiqued());
    }

    public function test_canBeDepiqued_returns_false_when_already_validated(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->assertFalse($admission->canBeDepiqued());
    }

    public function test_canBeDepiqued_returns_false_when_not_discharged(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        $this->assertFalse($admission->canBeDepiqued());
    }

    public function test_canBeLiberee_returns_true_after_validation(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->assertTrue($admission->canBeLiberee());
    }

    public function test_canBeLiberee_returns_false_without_validation(): void
    {
        $admission = $this->dischargedAdmission();

        $this->assertFalse($admission->canBeLiberee());
    }

    // ─── Controller: validateDepiquage ───────────────────────────────────────

    public function test_aide_can_validate_depiquage(): void
    {
        $admission = $this->dischargedAdmission();
        $aide = $this->aide();

        $this->actingAs($aide)
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $admission->refresh();
        $this->assertSame($aide->id, $admission->depiquage_valide_par);
        $this->assertNotNull($admission->depiquage_valide_le);
        $this->assertTrue($admission->isDepiquageValide());
    }

    public function test_admin_can_validate_depiquage(): void
    {
        $admission = $this->dischargedAdmission();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($admin->id, $admission->fresh()->depiquage_valide_par);
    }

    public function test_doctor_cannot_validate_depiquage(): void
    {
        $admission = $this->dischargedAdmission();
        $doctor = $this->doctor();

        $this->actingAs($doctor)
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertForbidden();
    }

    public function test_validate_depiquage_blocks_when_no_medical_discharge(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->aide())
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($admission->fresh()->depiquage_valide_par);
    }

    public function test_validate_depiquage_blocks_when_already_validated(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($this->aide())
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ─── Controller: libererChambre ──────────────────────────────────────────

    public function test_aide_can_liberate_room_after_depiquage(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($this->aide())
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $admission->refresh();
        $this->assertNotNull($admission->discharged_at);
    }

    public function test_admin_can_liberate_room(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_liberer_chambre_blocks_without_depiquage_validation(): void
    {
        $admission = $this->dischargedAdmission();

        $this->actingAs($this->aide())
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_liberer_chambre_blocks_when_room_already_free(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
            'discharged_at' => null,
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($this->aide())
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_doctor_cannot_liberate_room(): void
    {
        $admission = $this->dischargedAdmission();
        $admission->update([
            'depiquage_valide_par' => $this->aide()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($this->doctor())
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertForbidden();
    }

    // ─── Order enforcement ───────────────────────────────────────────────────

    public function test_full_depiquage_flow(): void
    {
        $admission = $this->dischargedAdmission();
        $aide = $this->aide();

        // Step 1: validate depiquage
        $this->actingAs($aide)
            ->post(route('admissions.valider-depiquage', $admission))
            ->assertSessionHas('success');

        $this->assertTrue($admission->fresh()->isDepiquageValide());

        // Step 2: liberate room
        $this->actingAs($aide)
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertSessionHas('success');

        $this->assertNotNull($admission->fresh()->discharged_at);
    }

    public function test_liberer_chambre_is_blocked_without_prior_validation(): void
    {
        $admission = $this->dischargedAdmission();
        $aide = $this->aide();

        // Try to liberate without validating first — should fail
        $this->actingAs($aide)
            ->post(route('admissions.liberer-chambre', $admission))
            ->assertSessionHas('error');

        $this->assertFalse($admission->fresh()->isDepiquageValide());
    }
}
