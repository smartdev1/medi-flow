<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\SurgeryChecklist;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $medecin;
    private User $anesthetist;
    private User $nurse;
    private Patient $patient;
    private Visit $visit;
    private Surgery $surgery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->medecin = User::factory()->withRole(Role::Medecin)->create();
        $this->anesthetist = User::factory()->withRole(Role::Medecin)->create();
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
            "anesthetist_id" => $this->anesthetist->id,
            "procedure_name" => "Appendicectomie",
            "surgery_type" => "interne",
            "status" => Surgery::STATUS_CONSENT_SIGNED,
        ]);
    }

    public function test_audit_log_table_exists(): void
    {
        $this->assertTrue(\Schema::hasTable("audit_logs"));
    }

    public function test_checklist_completion_creates_audit_log(): void
    {
        $this->assertEquals(0, AuditLog::count());

        $response = $this->actingAs($this->anesthetist)
            ->post(route("surgery.checklist", ["surgery" => $this->surgery, "phase" => "sign_in"]), [
                "items" => ["identity_confirmed" => true, "site_marked" => true],
                "notes" => "Patient verified",
            ]);

        if ($response->status() !== 302) {
            dump("Status: " . $response->status());
            dump("Content: " . $response->getContent());
        }

        $response->assertStatus(302);
        $this->assertEquals(1, AuditLog::count());

        $log = AuditLog::first();
        dump("Audit log: " . json_encode($log ? $log->toArray() : "null"));

        $this->assertEquals("checklist_completed", $log->action);
        $this->assertEquals("sign_in", $log->new_values["phase"]);
        $this->assertArrayHasKey("items", $log->new_values);
        $this->assertArrayHasKey("checklist_id", $log->new_values);
        $this->assertEquals($this->anesthetist->id, $log->user_id);
    }
}
