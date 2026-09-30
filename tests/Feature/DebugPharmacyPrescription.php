<?php

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('debug pharmacy prescription store', function () {
    $user = User::where('role', Role::DocteurGeneral)->firstOrFail();
    $patient = Patient::firstOrFail();
    $medication = Medication::firstOrFail();

    $this->actingAs($user);

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

    // This will show the actual error
    $response->assertStatus(302);
    expect(Prescription::count())->toBeGreaterThan(0);
});
