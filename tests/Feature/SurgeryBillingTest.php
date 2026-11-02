<?php

namespace Tests\Feature;

use App\Enums\ActCategory;
use App\Models\Act;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\ActSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_surgery_with_anesthetist_adds_anesthesia_to_invoice(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $chirurgieAct = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 50000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $chirurgieAct->id,
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_COMPLETED,
            'anesthetist_id' => User::factory(),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $anesthAct = Act::where('code', 'BLOC-ANESTH-GEN')->first();
        $this->assertNotNull($anesthAct);

        $expectedSubtotal = $chirurgieAct->base_price + $anesthAct->base_price;

        $this->assertSame($expectedSubtotal, $invoice->subtotal);
        $this->assertArrayHasKey(ActCategory::Chirurgie->value, $invoice->breakdown);
        $this->assertSame($chirurgieAct->base_price, $invoice->breakdown[ActCategory::Chirurgie->value]);
        $this->assertArrayHasKey(ActCategory::Bloc->value, $invoice->breakdown);
        $this->assertSame($anesthAct->base_price, $invoice->breakdown[ActCategory::Bloc->value]);
    }

    public function test_incomplete_surgery_does_not_add_anesthesia(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $chirurgieAct = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 50000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $chirurgieAct->id,
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_SCHEDULED,
            'anesthetist_id' => User::factory(),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        // Only the chirurgie act should be billed, no anesthesia
        $this->assertSame($chirurgieAct->base_price, $invoice->subtotal);
        $this->assertArrayNotHasKey(ActCategory::Bloc->value, $invoice->breakdown);
    }

    public function test_completed_surgery_without_anesthetist_no_anesthesia(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $chirurgieAct = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 50000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $chirurgieAct->id,
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_COMPLETED,
            'anesthetist_id' => null,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $this->assertSame($chirurgieAct->base_price, $invoice->subtotal);
        $this->assertArrayNotHasKey(ActCategory::Bloc->value, $invoice->breakdown);
    }

    public function test_multiple_completed_surgeries_all_billed(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $chirurgieAct = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 50000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryInterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $chirurgieAct->id,
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_COMPLETED,
            'anesthetist_id' => User::factory(),
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_COMPLETED,
            'anesthetist_id' => User::factory(),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $anesthAct = Act::where('code', 'BLOC-ANESTH-GEN')->first();
        $expectedSubtotal = $chirurgieAct->base_price + ($anesthAct->base_price * 2);

        $this->assertSame($expectedSubtotal, $invoice->subtotal);
        $this->assertArrayHasKey(ActCategory::Bloc->value, $invoice->breakdown);
        $this->assertSame($anesthAct->base_price * 2, $invoice->breakdown[ActCategory::Bloc->value]);
    }

    public function test_externe_surgery_billing_uses_bloc_location_plus_anesthesia(): void
    {
        $this->seed(ActSeeder::class);

        $patient = Patient::factory()->uninsured()->create();
        $chirurgieAct = Act::factory()->create([
            'category' => ActCategory::Chirurgie->value,
            'base_price' => 50000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->surgeryExterne()->create([
            'patient_id' => $patient->id,
            'act_id' => $chirurgieAct->id,
        ]);

        Surgery::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => Surgery::STATUS_COMPLETED,
            'anesthetist_id' => User::factory(),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->refresh();

        $blocLocationAct = Act::where('code', 'BLOC-LOCATION')->first();
        $anesthAct = Act::where('code', 'BLOC-ANESTH-GEN')->first();
        $this->assertNotNull($blocLocationAct);
        $this->assertNotNull($anesthAct);

        $expectedBlocTotal = $blocLocationAct->base_price + $anesthAct->base_price;
        $expectedSubtotal = $expectedBlocTotal;

        $this->assertSame($expectedSubtotal, $invoice->subtotal);
        $this->assertArrayHasKey(ActCategory::Bloc->value, $invoice->breakdown);
        $this->assertSame($expectedBlocTotal, $invoice->breakdown[ActCategory::Bloc->value]);
        // The chirurgie act should NOT appear — externe substitutes BLOC-LOCATION
        $this->assertArrayNotHasKey(ActCategory::Chirurgie->value, $invoice->breakdown);
    }
}
