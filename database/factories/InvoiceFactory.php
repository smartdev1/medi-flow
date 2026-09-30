<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'status' => \App\Models\Invoice::STATUS_PENDING,
            'subtotal' => 0,
            'insurance_amount' => 0,
            'patient_amount' => 0,
            'paid_amount' => 0,
            'cashier_id' => null,
            'paid_at' => null,
            'printed_at' => null,
            'ticket_moderateur' => 20,
            'base_amount' => 0,
            'insurance_name' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Invoice::STATUS_PENDING,
        ]);
    }

    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Invoice::STATUS_AWAITING_PAYMENT,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Invoice::STATUS_PAID,
            'paid_amount' => 10000,
            'paid_at' => now(),
        ]);
    }

    public function withAmounts(int $subtotal, int $insurance = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'insurance_amount' => $insurance,
            'patient_amount' => $subtotal - $insurance,
        ]);
    }
}
