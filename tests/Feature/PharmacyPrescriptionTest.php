<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharmacy_prescription_store_creates_prescription(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();

        $medication = Medication::create([
            'name' => 'Test Med',
            'stock_quantity' => 100,
            'unit_price' => 500,
        ]);

        $this->actingAs($user);

        $initialCount = Prescription::count();

        $response = $this->post(route('prescriptions.store'), [
            'patient_id' => $patient->id,
            'date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'medication_id' => $medication->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialCount + 1, Prescription::count());

        $latest = Prescription::latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertEquals($patient->id, $latest->patient_id);
    }
}
