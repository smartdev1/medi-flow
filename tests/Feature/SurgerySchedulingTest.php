<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgerySchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_medecin_can_create_surgery_on_visit_with_surgery_type(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($medecin)->post(route('surgery.store', $visit), [
            'procedure_name' => 'Appendicectomie',
            'scheduled_at' => '2026-11-15 09:00:00',
            'anesthetist_id' => $medecin->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surgeries', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'anesthetist_id' => $medecin->id,
            'procedure_name' => 'Appendicectomie',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);
    }

    public function test_store_copies_surgery_type_from_visit(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();

        // Visit with interne surgery_type
        $visitInterne = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $this->actingAs($medecin)->post(route('surgery.store', $visitInterne), [
            'procedure_name' => 'Internal procedure',
        ]);

        $surgeryInterne = Surgery::where('visit_id', $visitInterne->id)->first();
        $this->assertNotNull($surgeryInterne);
        $this->assertSame('interne', $surgeryInterne->surgery_type);

        // Visit with externe surgery_type
        $visitExterne = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
        ]);

        $this->actingAs($medecin)->post(route('surgery.store', $visitExterne), [
            'procedure_name' => 'External procedure',
        ]);

        $surgeryExterne = Surgery::where('visit_id', $visitExterne->id)->first();
        $this->assertNotNull($surgeryExterne);
        $this->assertSame('externe', $surgeryExterne->surgery_type);
    }

    public function test_update_changes_schedule_and_anesthetist(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $anesthetist = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'procedure_name' => 'Original',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)->patch(route('surgery.update', $surgery), [
            'procedure_name' => 'Updated procedure',
            'scheduled_at' => '2026-12-01 14:00:00',
            'anesthetist_id' => $anesthetist->id,
            'notes' => 'Patient needs special prep.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertSame('Updated procedure', $surgery->procedure_name);
        $this->assertSame($anesthetist->id, $surgery->anesthetist_id);
        $this->assertSame('Patient needs special prep.', $surgery->notes);
        $this->assertSame('2026-12-01 14:00:00', $surgery->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_cancel_sets_status_to_cancelled(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'procedure_name' => 'To cancel',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)->delete(route('surgery.destroy', $surgery));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertSame(Surgery::STATUS_CANCELLED, $surgery->status);
        // Record still exists — not deleted
        $this->assertDatabaseHas('surgeries', ['id' => $surgery->id]);
    }

    public function test_cannot_cancel_non_scheduled_surgery(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'procedure_name' => 'In progress',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($medecin)->delete(route('surgery.destroy', $surgery));

        $response->assertForbidden();
        $surgery->refresh();
        $this->assertSame(Surgery::STATUS_IN_PROGRESS, $surgery->status);
    }

    public function test_cannot_update_completed_surgery(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'procedure_name' => 'Done',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($medecin)->patch(route('surgery.update', $surgery), [
            'procedure_name' => 'Should fail',
        ]);

        $response->assertForbidden();
    }

    public function test_non_medecin_cannot_access_surgery_routes(): void
    {
        $caissier = User::factory()->withRole(Role::Caissier)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $caissier->id,
            'procedure_name' => 'Test',
            'surgery_type' => 'interne',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        // Store
        $response = $this->actingAs($caissier)->post(route('surgery.store', $visit), [
            'procedure_name' => 'Unauthorized',
        ]);
        $response->assertForbidden();

        // Update
        $response = $this->actingAs($caissier)->patch(route('surgery.update', $surgery), [
            'procedure_name' => 'Unauthorized',
        ]);
        $response->assertForbidden();

        // Destroy
        $response = $this->actingAs($caissier)->delete(route('surgery.destroy', $surgery));
        $response->assertForbidden();
    }
}
