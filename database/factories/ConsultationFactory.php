<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'doctor_id' => User::factory(),
            'status' => \App\Models\Consultation::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'vitals' => null,
            'notes' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Consultation::STATUS_IN_PROGRESS,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Consultation::STATUS_CLOSED,
        ]);
    }
}
