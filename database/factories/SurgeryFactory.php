<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurgeryFactory extends Factory
{
    protected $model = Surgery::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'surgeon_id' => User::factory(),
            'anesthetist_id' => null,
            'scheduled_at' => now()->addDay(),
            'status' => Surgery::STATUS_SCHEDULED,
            'surgery_type' => 'interne',
            'referring_doctor_name' => null,
            'procedure_name' => fake()->sentence(3),
            'pre_anesthetic_notes' => null,
            'consent_signed_at' => null,
            'consent_signed_by' => null,
            'notes' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
