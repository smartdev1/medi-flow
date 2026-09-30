<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashierSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cashier_id' => User::factory(),
            'opened_at' => now(),
            'closed_at' => null,
            'opening_balance' => 0,
            'closing_balance' => null,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => null,
        ]);
    }

    public function closed(int $closingBalance = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
            'closing_balance' => $closingBalance,
        ]);
    }
}
