<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'prescription_id' => null,
            'act_id' => null,
            'service' => fake()->randomElement(['Consultation', 'Laboratoire', 'Pharmacie', 'Hospitalisation']),
            'base_amount' => fake()->numberBetween(5000, 50000),
            'taux' => 100,
            'amount' => fake()->numberBetween(5000, 50000),
            'method' => fake()->randomElement(['Espèces', 'Carte bancaire', 'Mobile Money']),
            'status' => \App\Models\Payment::STATUS_PENDING,
            'invoice_id' => null,
            'cashier_id' => null,
            'cashier_session_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Payment::STATUS_PAID,
            'cashier_id' => User::factory(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Payment::STATUS_PARTIAL,
        ]);
    }

    public function forVisit(Visit $visit): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
        ]);
    }
}
