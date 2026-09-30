<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'name' => fake()->randomElement(['NFS', 'Glycémie', 'Radio poumon', 'ECG', 'Hématologie']),
            'status' => \App\Models\Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
            'price' => fake()->numberBetween(5000, 50000),
        ];
    }

    public function awaitingSample(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Exam::STATUS_AWAITING_SAMPLE,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Exam::STATUS_IN_PROGRESS,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Exam::STATUS_SENT,
        ]);
    }
}
