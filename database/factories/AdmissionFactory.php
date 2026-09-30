<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'room_id' => Room::factory(),
            'bed_number' => 1,
            'reason' => fake()->sentence(),
            'attending' => fake()->name(),
            'status' => \App\Models\Admission::STATUS_STABLE,
            'admitted_at' => now(),
            'discharged_at' => null,
        ];
    }

    public function observation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Admission::STATUS_OBSERVATION,
        ]);
    }

    public function stable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Admission::STATUS_STABLE,
        ]);
    }

    public function discharged(): static
    {
        return $this->state(fn (array $attributes) => [
            'discharged_at' => now()->subDays(fake()->numberBetween(1, 7)),
        ]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $patient->id,
        ]);
    }
}
