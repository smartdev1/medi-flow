<?php

namespace Database\Factories;

use App\Models\Act;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'CSM-'.random_int(1000, 9999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'sex' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'phone' => fake()->phoneNumber(),
            'coverage' => fake()->randomElement(['Assuré', 'Non assuré']),
            'insurance_company_id' => null,
            'allergies' => null,
            'guardian_name' => null,
            'emergency_contact' => null,
        ];
    }

    public function insured(): static
    {
        return $this->state(fn (array $attributes) => [
            'coverage' => 'Assuré',
            'insurance_company_id' => \App\Models\InsuranceCompany::factory(),
        ]);
    }

    public function uninsured(): static
    {
        return $this->state(fn (array $attributes) => [
            'coverage' => 'Non assuré',
            'insurance_company_id' => null,
        ]);
    }
}
