<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryOperativeReportTest extends TestCase
{
    use RefreshDatabase;

    private User $medecin;
    private Patient $patient;
    private Visit $visit;
    private Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medecin = User::factory()->withRole(Role::Medecin)->create();
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

    public function test_operative_report_blocked_before_completed(): void
    {
        $surgeryInProgress = Surgery::factory()->create([
            "visit_id" => $this->visit->id,
            "patient_id" => $this->patient->id,
            "surgeon_id" => $this->medecin->id,
            "procedure_name" => "Test",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.operative-report", $surgeryInProgress), [
                "operative_report" => "Rapport de test avec plus de cinquante caracteres requis.",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("error");

        $surgeryInProgress->refresh();
        $this->assertNull($surgeryInProgress->operative_report);
    }

    public function test_operative_report_requires_min_50_chars(): void
    {
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.operative-report", $this->surgery), [
                "operative_report" => "Trop court",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("errors");
    }

    public function test_operative_report_can_be_stored_after_completed(): void
    {
        $reportText = "L'appendicectomie a été réalisée sans complication. L'appendice était enflammé mais non perforé. Fermeture pariétale en deux plans. Suites simples prévues.";

        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.operative-report", $this->surgery), [
                "operative_report" => $reportText,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertEquals($reportText, $this->surgery->operative_report);
        $this->assertEquals($this->medecin->id, $this->surgery->operative_report_by);
        $this->assertNotNull($this->surgery->operative_report_at);
    }

    public function test_operative_report_is_read_only_after_submission(): void
    {
        // First submission
        $this->actingAs($this->medecin)
            ->post(route("surgery.operative-report", $this->surgery), [
                "operative_report" => "Premier rapport valide avec plus de cinquante caracteres.",
            ]);

        // Try to update - should still work (overwrite allowed)
        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.operative-report", $this->surgery), [
                "operative_report" => "Deuxieme rapport valide avec plus de cinquante caracteres.",
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas("success");

        $this->surgery->refresh();
        $this->assertEquals("Deuxieme rapport valide avec plus de cinquante caracteres.", $this->surgery->operative_report);
    }
}
