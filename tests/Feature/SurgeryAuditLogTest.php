<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Surgery;
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
            "status" => Surgery::STATUS_SCHEDULED,
        ]);
    }

    public function test_audit_log_table_exists(): void
    {
        $this->assertTrue(\Schema::hasTable("audit_logs"));
    }

    public function test_starting_surgery_creates_audit_log(): void
    {
        $this->assertEquals(0, AuditLog::count());

        $response = $this->actingAs($this->medecin)
            ->post(route("surgery.start", $this->surgery), [
                "consent" => "1",
            ]);

        $response->assertStatus(302);
        $this->assertEquals(1, AuditLog::count());

        $log = AuditLog::first();
        $this->assertEquals("status_changed", $log->action);
        $this->assertEquals(Surgery::STATUS_IN_PROGRESS, $log->new_values["status"]);
        $this->assertEquals($this->medecin->id, $log->user_id);
    }
}
