<?php

namespace Tests\Integration;

use App\Enums\Role;
use App\Models\Act;
use App\Models\Admission;
use App\Models\Exam;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRecalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_recalculate_includes_act_price(): void
    {
        $patient = Patient::factory()->create(['coverage' => 'Non assuré']);
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $act = Act::factory()->create(['base_price' => 15000]);

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $visit->update(['act_id' => $act->id]);

        $invoice->recalculate();

        $this->assertSame(15000, $invoice->fresh()->subtotal);
        $this->assertSame(15000, $invoice->fresh()->patient_amount);
        $this->assertSame(0, $invoice->fresh()->insurance_amount);
    }

    public function test_invoice_recalculate_with_insurance_applies_rate(): void
    {
        $company = InsuranceCompany::factory()->create(['rate' => 80]);
        $patient = Patient::factory()->create([
            'coverage' => 'Assuré',
            'insurance_company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $act = Act::factory()->create(['base_price' => 10000]);

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $visit->update(['act_id' => $act->id]);

        $invoice->recalculate();

        $this->assertSame(10000, $invoice->fresh()->subtotal);
        $this->assertSame(8000, $invoice->fresh()->insurance_amount);
        $this->assertSame(2000, $invoice->fresh()->patient_amount);
    }

    public function test_invoice_recalculate_includes_exams(): void
    {
        $patient = Patient::factory()->create(['coverage' => 'Non assuré']);
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        Exam::factory()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'price' => 25000,
        ]);

        $invoice->recalculate();

        $this->assertSame(25000, $invoice->fresh()->subtotal);
    }

    public function test_invoice_recalculate_includes_admissions_with_daily_rate(): void
    {
        $patient = Patient::factory()->create(['coverage' => 'Non assuré']);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'arrived_at' => now()->subDays(2)]);
        $room = Room::factory()->create();

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        Admission::factory()->create([
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'admitted_at' => $visit->arrived_at,
            'discharged_at' => $visit->arrived_at->copy()->addDays(2),
        ]);

        $invoice->recalculate();

        // 3 days * 10000 FCFA default rate = 30000
        $this->assertSame(30000, $invoice->fresh()->subtotal);
    }

    public function test_invoice_recalculate_with_insured_price_uses_insured_price(): void
    {
        $company = InsuranceCompany::factory()->create(['rate' => 80]);
        $patient = Patient::factory()->create([
            'coverage' => 'Assuré',
            'insurance_company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id]);
        $act = Act::factory()->create([
            'base_price' => 10000,
            'insured_price' => 5000,
        ]);

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $visit->update(['act_id' => $act->id]);

        $invoice->recalculate();

        // When insured, uses insured_price (5000), not base_price (10000)
        $this->assertSame(5000, $invoice->fresh()->subtotal);
        $this->assertSame(4000, $invoice->fresh()->insurance_amount);
        $this->assertSame(1000, $invoice->fresh()->patient_amount);
    }
}
