<?php

namespace Tests\Unit;

use App\Models\Visit;
use PHPUnit\Framework\TestCase;

class VisitStatusTest extends TestCase
{
    public function test_has_all_expected_status_constants(): void
    {
        $expected = [
            'STATUS_WAITING',
            'STATUS_URGENT',
            'STATUS_IN_CONSULTATION',
            'STATUS_CONSULTATION_CLOSED',
            'STATUS_HOSPITALIZATION_REQUESTED',
            'STATUS_OBSERVATION_REQUESTED',
            'STATUS_RETURNED_FROM_OBSERVATION',
            'STATUS_HOSPITALIZED',
            'STATUS_REFERRED',
        ];

        foreach ($expected as $constant) {
            $this->assertTrue(
                defined(Visit::class.'::'.$constant),
                "Missing constant {$constant} on Visit model"
            );
        }
    }

    public function test_status_constants_have_correct_values(): void
    {
        $this->assertSame('En attente', Visit::STATUS_WAITING);
        $this->assertSame('Prioritaire', Visit::STATUS_URGENT);
        $this->assertSame('En consultation', Visit::STATUS_IN_CONSULTATION);
        $this->assertSame('Consultation terminée', Visit::STATUS_CONSULTATION_CLOSED);
        $this->assertSame('Demande hospitalisation', Visit::STATUS_HOSPITALIZATION_REQUESTED);
        $this->assertSame('Demande observation', Visit::STATUS_OBSERVATION_REQUESTED);
        $this->assertSame('Retour observation', Visit::STATUS_RETURNED_FROM_OBSERVATION);
        $this->assertSame('Hospitalisé', Visit::STATUS_HOSPITALIZED);
        $this->assertSame('Orienté', Visit::STATUS_REFERRED);
    }

    public function test_status_tone_accessor_returns_brand_for_in_consultation(): void
    {
        $visit = new Visit(['status' => Visit::STATUS_IN_CONSULTATION]);
        $this->assertSame('brand', $visit->status_tone);
    }

    public function test_status_tone_accessor_returns_danger_for_urgent(): void
    {
        $visit = new Visit(['status' => Visit::STATUS_URGENT]);
        $this->assertSame('danger', $visit->status_tone);
    }

    public function test_status_tone_accessor_returns_warning_for_hospitalization_related(): void
    {
        $this->assertSame('warning', (new Visit(['status' => Visit::STATUS_HOSPITALIZATION_REQUESTED]))->status_tone);
        $this->assertSame('warning', (new Visit(['status' => Visit::STATUS_OBSERVATION_REQUESTED]))->status_tone);
        $this->assertSame('warning', (new Visit(['status' => Visit::STATUS_HOSPITALIZED]))->status_tone);
        $this->assertSame('warning', (new Visit(['status' => Visit::STATUS_RETURNED_FROM_OBSERVATION]))->status_tone);
    }

    public function test_status_tone_accessor_returns_success_for_closed_and_referred(): void
    {
        $this->assertSame('success', (new Visit(['status' => Visit::STATUS_CONSULTATION_CLOSED]))->status_tone);
        $this->assertSame('success', (new Visit(['status' => Visit::STATUS_REFERRED]))->status_tone);
    }

    public function test_status_tone_accessor_returns_neutral_by_default(): void
    {
        $visit = new Visit(['status' => Visit::STATUS_WAITING]);
        $this->assertSame('neutral', $visit->status_tone);
    }

    public function test_scope_active_is_defined(): void
    {
        $reflection = new \ReflectionClass(Visit::class);
        $this->assertTrue($reflection->hasMethod('scopeActive'));
    }
}
