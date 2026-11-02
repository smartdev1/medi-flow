<?php

namespace Tests\Feature;

use App\Enums\ActCategory;
use App\Models\Act;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\ClinicMetrics;
use Carbon\Carbon;
use Database\Seeders\ActSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryAdmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_surgery_type_column_exists(): void
    {
        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create(['category' => ActCategory::Chirurgie->value]);

        $visitNull = Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'surgery_type' => null,
        ]);
        $this->assertNull($visitNull->fresh()->surgery_type);

        $visitInterne = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);
        $this->assertSame('interne', $visitInterne->fresh()->surgery_type);

        $visitExterne = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);
        $this->assertSame('externe', $visitExterne->fresh()->surgery_type);
    }

    public function test_registration_with_surgery_interne_bills_surgery_act(): void
    {
        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 15000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $this->assertSame(15000, $invoice->subtotal);
        $this->assertSame(15000, $invoice->patient_amount);
        $this->assertArrayHasKey(ActCategory::Chirurgie->value, $invoice->breakdown);
        $this->assertSame(15000, $invoice->breakdown[ActCategory::Chirurgie->value]);
    }

    public function test_registration_with_surgery_externe_bills_bloc_location(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 15000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $blocAct = Act::where('code', 'BLOC-LOCATION')->first();
        $this->assertNotNull($blocAct);

        $this->assertSame($blocAct->base_price, $invoice->subtotal);
        $this->assertSame($blocAct->base_price, $invoice->patient_amount);
        $this->assertArrayHasKey(ActCategory::Bloc->value, $invoice->breakdown);
        $this->assertSame($blocAct->base_price, $invoice->breakdown[ActCategory::Bloc->value]);
        $this->assertArrayNotHasKey(ActCategory::Chirurgie->value, $invoice->breakdown);
    }

    public function test_registration_without_surgery_type_unchanged(): void
    {
        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 15000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'surgery_type' => null,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $this->assertSame(15000, $invoice->subtotal);
        $this->assertArrayHasKey(ActCategory::Chirurgie->value, $invoice->breakdown);
    }

    public function test_externe_without_bloc_location_act_no_error(): void
    {
        // Do NOT seed ActSeeder, so BLOC-LOCATION does not exist.
        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 15000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $this->assertSame(0, $invoice->subtotal);
        $this->assertSame(0, $invoice->patient_amount);
        $this->assertEmpty($invoice->breakdown);
    }

    public function test_bloc_revenue_appears_in_clinic_metrics(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $act = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 15000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->update(['status' => Invoice::STATUS_PAID]);

        $finance = ClinicMetrics::finance(
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay()
        );

        $this->assertArrayHasKey('Bloc opératoire', $finance['by_service_category']);

        $blocAct = Act::where('code', 'BLOC-LOCATION')->first();
        $this->assertSame($blocAct->base_price, $finance['by_service_category']['Bloc opératoire']);
    }
}
