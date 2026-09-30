<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'requested_by' => \App\Models\User::factory(),
            'dispensed_by' => null,
            'status' => \App\Models\Prescription::STATUS_PENDING,
            'requested_at' => now(),
            'dispensed_at' => null,
            'medications_summary' => fake()->randomElement(['Paracétamol 1g', 'Amoxicilline 500mg', 'Ibuprofène 400mg']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Prescription::STATUS_PENDING,
        ]);
    }

    public function dispensed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Prescription::STATUS_DISPENSED,
            'dispensed_by' => \App\Models\User::factory(),
            'dispensed_at' => now(),
        ]);
    }
}
