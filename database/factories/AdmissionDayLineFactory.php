<?php

namespace Database\Factories;

use App\Models\Admission;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionDayLineFactory extends Factory
{
    public function definition(): array
    {
        $admission = Admission::inRandomOrder()->first() ?? Admission::factory();

        return [
            'admission_id' => is_object($admission) ? $admission->id : $admission,
            'day_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'amount' => fake()->numberBetween(10_000, 15_000),
        ];
    }
}
