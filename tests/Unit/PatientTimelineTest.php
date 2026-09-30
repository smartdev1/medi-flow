<?php

namespace Tests\Unit;

use App\Support\PatientTimeline;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Payment;
use App\Models\Exam;
use App\Models\Prescription;
use App\Models\Admission;
use App\Models\Referral;
use App\Models\Consultation;
use PHPUnit\Framework\TestCase;

class PatientTimelineTest extends TestCase
{
    public function test_build_method_exists(): void
    {
        $reflection = new \ReflectionClass(PatientTimeline::class);
        $this->assertTrue($reflection->hasMethod('build'));
        $this->assertTrue($reflection->hasMethod('buildForVisit'));
    }

    public function test_build_returns_array(): void
    {
        $reflection = new \ReflectionMethod(PatientTimeline::class, 'build');
        $this->assertSame('array', $reflection->getReturnType()?->getName());
    }

    public function test_build_for_visit_returns_array(): void
    {
        $reflection = new \ReflectionMethod(PatientTimeline::class, 'buildForVisit');
        $this->assertSame('array', $reflection->getReturnType()?->getName());
    }

    public function test_build_method_is_static(): void
    {
        $reflection = new \ReflectionMethod(PatientTimeline::class, 'build');
        $this->assertTrue($reflection->isStatic());
    }

    public function test_build_for_visit_method_is_static(): void
    {
        $reflection = new \ReflectionMethod(PatientTimeline::class, 'buildForVisit');
        $this->assertTrue($reflection->isStatic());
    }

    public function test_format_steps_private_method_exists(): void
    {
        $reflection = new \ReflectionClass(PatientTimeline::class);
        $this->assertTrue($reflection->hasMethod('formatSteps'));
        $this->assertTrue($reflection->getMethod('formatSteps')->isPrivate());
    }

    public function test_build_steps_private_method_exists(): void
    {
        $reflection = new \ReflectionClass(PatientTimeline::class);
        $this->assertTrue($reflection->hasMethod('buildSteps'));
        $this->assertTrue($reflection->getMethod('buildSteps')->isPrivate());
    }
}
