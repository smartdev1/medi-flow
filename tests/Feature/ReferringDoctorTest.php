<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferringDoctorTest extends TestCase
{
    use RefreshDatabase;

    public function test_externe_surgery_stores_referring_doctor_name(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($medecin)->post(route('surgery.store', $visit), [
            'procedure_name' => 'Appendicectomie',
            'referring_doctor_name' => 'Dr. Konan Yao',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surgeries', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgery_type' => 'externe',
            'referring_doctor_name' => 'Dr. Konan Yao',
        ]);
    }

    public function test_interne_surgery_ignores_referring_doctor_name(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($medecin)->post(route('surgery.store', $visit), [
            'procedure_name' => 'Hernie inguinale',
            'referring_doctor_name' => 'Dr. Someone',
        ]);

        $response->assertRedirect();

        $surgery = Surgery::where('visit_id', $visit->id)->first();
        $this->assertNotNull($surgery);
        $this->assertNull($surgery->referring_doctor_name);
    }

    public function test_referring_doctor_name_nullable(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($medecin)->post(route('surgery.store', $visit), [
            'procedure_name' => 'Cholecystectomie',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery = Surgery::where('visit_id', $visit->id)->first();
        $this->assertNotNull($surgery);
        $this->assertNull($surgery->referring_doctor_name);
    }

    public function test_referring_doctor_appears_on_bloc_dashboard(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
        ]);

        $surgery = Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $medecin->id,
            'surgery_type' => 'externe',
            'referring_doctor_name' => 'Dr. Ama Kouadio',
            'scheduled_at' => today()->setHour(10),
        ]);

        $response = $this->actingAs($medecin)->get(route('bloc.index', [
            'date' => today()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dr. Ama Kouadio');
    }
}
