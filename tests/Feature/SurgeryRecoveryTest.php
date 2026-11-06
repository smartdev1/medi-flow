<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private User $medecin;
    private User $nurse;
    private Patient $patient;
    private Visit $visit;
    private Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medecin = User::factory()->withRole(Role::Medecin)->create();
        $this->nurse = User::factory()->withRole(Role::AideSoignante)->create();
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            "patient_id" => $this->patient->id,
            "surgery_type" => "interne",
        ]);
        $this->surgery = Surgery::factory()->create([
            "visit_id" => $this->visit->id,
            "patient_id" => $this->patient->id,
            "surgeon_id" => $this->medecin->id,
            "procedure_name" => "Appendicectomie",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_COMPLETED,
        ]);
    }

    public function test_recovery_start_blocked_before_completed(): void
    {
        $surgeryInProgress = Surgery::factory()->create([
            "visit_id" => $this->visit->id,
            "patient_id" => $this->patient->id,
            "surgeon_id" => $this->medecin->id,
            "procedure_name" => "Test",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $surgeryInProgress));

        $response->assertStatus(302);
        $response->assertSessionHas("error");

        $surgeryInProgress->refresh();
        $this->assertNull($surgeryInProgress->recovery_started_at);
    }

    public function test_recovery_cannot_start_twice(): void
    {
        // Start recovery
        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        // Try to start again
        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("error");
    }

    public function test_recovery_can_start_after_completed(): void
    {
        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertNotNull($this->surgery->recovery_started_at);
        $this->assertEquals($this->nurse->id, $this->surgery->recovery_nurse_id);
        $this->assertNull($this->surgery->recovery_ended_at);
    }

    public function test_recovery_end_requires_notes_min_10_chars(): void
    {
        // Start recovery first
        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => "Court",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("errors");
    }

    public function test_recovery_cannot_end_before_start(): void
    {
        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => "Notes de surveillance valides avec plus de dix caracteres.",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("error");

        $this->surgery->refresh();
        $this->assertNull($this->surgery->recovery_ended_at);
    }

    public function test_recovery_can_end_after_start(): void
    {
        // Start recovery
        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        $notes = "Patient stable, saturation 98%, douleur 2/10, pas de saignement, constantes normales.";

        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => $notes,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertNotNull($this->surgery->recovery_ended_at);
        $this->assertEquals($notes, $this->surgery->recovery_notes);
    }

    public function test_recovery_cannot_end_twice(): void
    {
        // Start and end recovery
        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => "Notes valides pour la fin de surveillance.",
            ]);

        // Try to end again
        $response = $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => "Autres notes valides pour la fin.",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("error");
    }

    public function test_any_medecin_can_start_recovery(): void
    {
        $otherMedecin = User::factory()->withRole(Role::Medecin)->create();

        $response = $this->actingAs($otherMedecin)
            ->post(route("surgery.recovery.start", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("success");
    }

    public function test_aide_soignante_can_end_recovery(): void
    {
        // Start with nurse
        $this->actingAs($this->nurse)
            ->post(route("surgery.recovery.start", $this->surgery));

        // End with another nurse
        $otherNurse = User::factory()->withRole(Role::AideSoignante)->create();

        $response = $this->actingAs($otherNurse)
            ->post(route("surgery.recovery.end", $this->surgery), [
                "recovery_notes" => "Fin de surveillance par une autre infirmière.",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");
    }
}
