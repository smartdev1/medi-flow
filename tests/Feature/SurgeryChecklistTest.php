<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgeryChecklist;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $medecin;
    private User $anesthetist;
    private Patient $patient;
    private Visit $visit;
    private Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medecin = User::factory()->withRole(Role::Medecin)->create();
        $this->anesthetist = User::factory()->withRole(Role::Medecin)->create();
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            "patient_id" => $this->patient->id,
            "surgery_type" => "interne",
        ]);
        $this->surgery = Surgery::factory()->create([
            "visit_id" => $this->visit->id,
            "patient_id" => $this->patient->id,
            "surgeon_id" => $this->medecin->id,
            "anesthetist_id" => $this->anesthetist->id,
            "procedure_name" => "Appendicectomie",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_CONSENT_SIGNED,
        ]);
    }

    public function test_checklist_creation_for_each_phase(): void
    {
        // Sign In must be done by anesthetist
        $response = $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true, "site_marked" => true],
                "notes" => "Test notes for sign_in",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $checklist = SurgeryChecklist::where("surgery_id", $this->surgery->id)
            ->where("phase", "sign_in")
            ->first();

        $this->assertNotNull($checklist);
        $this->assertEquals("sign_in", $checklist->phase);
        $this->assertEquals($this->anesthetist->id, $checklist->checked_by);

        // Time Out and Sign Out can be done by surgeon
        foreach (["time_out", "sign_out"] as $phase) {
            $response = $this->actingAs($this->medecin)
                ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => $phase]), [
                    "items" => ["item1" => true, "item2" => false],
                    "notes" => "Test notes for $phase",
                ]);

            $response->assertStatus(302);
            $response->assertSessionHas("success");

            $checklist = SurgeryChecklist::where("surgery_id", $this->surgery->id)
                ->where("phase", $phase)
                ->first();

            $this->assertNotNull($checklist);
            $this->assertEquals($phase, $checklist->phase);
            $this->assertEquals($this->medecin->id, $checklist->checked_by);
        }
    }

    public function test_duplicate_phase_is_rejected(): void
    {
        // First submission
        $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["item1" => true],
            ]);

        // Second submission should fail with redirect and error
        $response = $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["item1" => true],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("error");
    }

    public function test_surgery_cannot_start_without_sign_in(): void
    {
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.start", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("error");

        $this->surgery->refresh();
        $this->assertEquals(Surgery::STATUS_CONSENT_SIGNED, $this->surgery->status);
    }

    public function test_surgery_can_start_after_sign_in(): void
    {
        // Complete Sign In checklist
        $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => [
                    "identity_confirmed" => true,
                    "site_marked" => true,
                    "anesthesia_check" => true,
                    "allergies_verified" => true,
                    "airway_risk" => true,
                    "blood_loss_risk" => true,
                ],
            ]);

        // Now start surgery
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.start", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertEquals(Surgery::STATUS_IN_PROGRESS, $this->surgery->status);
        $this->assertNotNull($this->surgery->started_at);
    }

    public function test_surgery_cannot_complete_without_sign_out(): void
    {
        // Start surgery first (with Sign In)
        $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true],
            ]);

        $this->actingAs($this->medecin)
            ->post(route("surgery.start", $this->surgery));

        $this->surgery->refresh();
        $this->assertEquals(Surgery::STATUS_IN_PROGRESS, $this->surgery->status);

        // Try to complete without Sign Out
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.complete", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("error");

        $this->surgery->refresh();
        $this->assertEquals(Surgery::STATUS_IN_PROGRESS, $this->surgery->status);
    }

    public function test_surgery_can_complete_after_sign_out(): void
    {
        // Start surgery
        $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true],
            ]);

        $this->actingAs($this->medecin)
            ->post(route("surgery.start", $this->surgery));

        // Complete Sign Out checklist
        $this->actingAs($this->medecin)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_out"]), [
                "items" => [
                    "procedure_confirmed" => true,
                    "instrument_count" => true,
                    "specimen_labelled" => true,
                    "equipment_issues" => false,
                    "postop_plan" => true,
                ],
            ]);

        // Now complete surgery
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.complete", $this->surgery));

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertEquals(Surgery::STATUS_COMPLETED, $this->surgery->status);
        $this->assertNotNull($this->surgery->completed_at);
    }

    public function test_invalid_phase_returns_404(): void
    {
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "invalid_phase"]), [
                "items" => ["item1" => true],
            ]);

        $response->assertStatus(404);
    }

    public function test_sign_in_requires_anesthetist_when_assigned(): void
    {
        // Anesthetist is assigned, only they can fill Sign In
        $otherMedecin = User::factory()->withRole(Role::Medecin)->create();

        $response = $this->actingAs($otherMedecin)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("error");
    }

    public function test_sign_in_allowed_by_any_medecin_when_no_anesthetist(): void
    {
        // Create surgery without anesthetist
        $surgeryNoAnesthetist = Surgery::factory()->create([
            "visit_id" => $this->visit->id,
            "patient_id" => $this->patient->id,
            "surgeon_id" => $this->medecin->id,
            "anesthetist_id" => null,
            "procedure_name" => "Test",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_CONSENT_SIGNED,
        ]);

        $otherMedecin = User::factory()->withRole(Role::Medecin)->create();

        $response = $this->actingAs($otherMedecin)
            ->post(route("surgery.checklist", ["surgery" => $surgeryNoAnesthetist, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");
    }
}
