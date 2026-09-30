<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('CH-###'),
            'ward' => fake()->randomElement(['Urgence', 'Pédiatrie', 'Chirurgie', 'Médecine']),
            'capacity' => fake()->numberBetween(1, 4),
            'type' => fake()->randomElement([\App\Models\Room::TYPE_INDIVIDUELLE, \App\Models\Room::TYPE_GROUPE]),
            'daily_rate' => fake()->numberBetween(10_000, 50_000),
            'maintenance' => false,
        ];
    }

    public function withBeds(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $count,
        ]);
    }
}
