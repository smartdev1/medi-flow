<?php

namespace Database\Factories;

use App\Enums\ActCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'ACT-'.strtoupper(fake()->lexify('???')),
            'label' => fake()->randomElement(['Consultation générale', 'Consultation spécialisée', 'Urgence', 'Suivi']),
            'category' => fake()->randomElement(array_map(fn ($c) => $c->value, ActCategory::cases())),
            'base_price' => fake()->numberBetween(5000, 30000),
            'insured_price' => null,
            'roles' => null,
        ];
    }

    public function withInsuredPrice(int $price): static
    {
        return $this->state(fn (array $attributes) => [
            'insured_price' => $price,
        ]);
    }
}
