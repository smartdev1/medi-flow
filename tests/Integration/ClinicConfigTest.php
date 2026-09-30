<?php

namespace Tests\Integration;

use Tests\TestCase;

class ClinicConfigTest extends TestCase
{
    public function test_default_ticket_rate_is_set(): void
    {
        $rate = config('clinic.default_ticket_rate');
        $this->assertNotNull($rate);
        $this->assertIsInt($rate);
        $this->assertGreaterThan(0, $rate);
    }

    public function test_discharge_unpaid_threshold_is_set(): void
    {
        $threshold = config('clinic.discharge_unpaid_threshold');
        $this->assertNotNull($threshold);
        $this->assertIsInt($threshold);
        $this->assertGreaterThanOrEqual(0, $threshold);
    }

    public function test_default_vitals_is_array(): void
    {
        $vitals = config('clinic.default_vitals');
        $this->assertIsArray($vitals);
        $this->assertArrayHasKey('Médecin généraliste', $vitals);
    }

    public function test_payment_methods_is_array(): void
    {
        $methods = config('clinic.payment_methods');
        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);
    }

    public function test_payment_statuses_is_array(): void
    {
        $statuses = config('clinic.payment_statuses');
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('Payé', $statuses);
        $this->assertArrayHasKey('Partiellement payé', $statuses);
        $this->assertArrayHasKey('En attente', $statuses);
    }
}
