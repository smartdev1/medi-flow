<?php

namespace Database\Factories;

use App\Models\Act;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'arrived_at' => now(),
            'reason' => fake()->sentence(),
            'act_id' => Act::factory(),
            'registered_by' => User::factory(),
            'status' => \App\Models\Visit::STATUS_WAITING,
            'urgent' => false,
            'vitals_taken_by' => null,
            'vitals_taken_at' => null,
            'discharged_at' => null,
            'surgery_type' => null,
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Visit::STATUS_WAITING,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Visit::STATUS_URGENT,
            'urgent' => true,
        ]);
    }

    public function inConsultation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Visit::STATUS_IN_CONSULTATION,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Visit::STATUS_CONSULTATION_CLOSED,
            'discharged_at' => now(),
        ]);
    }

    public function discharged(): static
    {
        return $this->state(fn (array $attributes) => [
            'discharged_at' => now(),
        ]);
    }

    public function surgeryInterne(): static
    {
        return $this->state(fn (array $attributes) => [
            'surgery_type' => 'interne',
        ]);
    }

    public function surgeryExterne(): static
    {
        return $this->state(fn (array $attributes) => [
            'surgery_type' => 'externe',
        ]);
    }
}
