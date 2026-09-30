<?php

namespace Tests\Unit;

use App\Enums\ActCategory;
use App\Models\Act;
use App\Models\Admission;
use App\Models\Exam;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le détail de facture est vérifié sur des enregistrements réels : la
 * facturation croise la visite, les examens du patient et les journées
 * d'admission, et un double sur ces relations typées ne prouve pas grand-chose.
 */
class InvoiceBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_produces_breakdown_with_consultation_and_hospitalisation(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        $act = Act::factory()->create([
            'base_price' => 15_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => now()->subDays(2),
        ]);

        $patient->exams()->createMany([
            ['name' => 'NFS', 'price' => 5_000, 'status' => Exam::STATUS_AWAITING_SAMPLE, 'requested_at' => now()],
            ['name' => 'Glycémie', 'price' => 3_000, 'status' => Exam::STATUS_AWAITING_SAMPLE, 'requested_at' => now()],
        ]);

        // Une journée d'hospitalisation facturée.
        $room = Room::factory()->create(['daily_rate' => 10_000]);
        $admission = Admission::factory()->forPatient($patient)->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'admitted_at' => now(),
        ]);
        $admission->syncDayLines();

        $invoice = Invoice::forVisit($visit);

        $this->assertSame(
            ['consultation' => 15_000, 'laboratoire' => 8_000, 'hospitalisation' => 10_000],
            $invoice->breakdown
        );

        $this->assertSame(33_000, $invoice->subtotal);
    }

    public function test_recalculate_excludes_zero_amount_categories(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        // Une visite sans acte et sans examen ne produit aucune catégorie.
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $invoice = Invoice::forVisit($visit);

        $this->assertSame([], $invoice->breakdown);
        $this->assertSame(0, $invoice->subtotal);
        $this->assertSame(0, $invoice->patient_amount);
    }

    public function test_uninsured_patient_has_no_third_party(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        $act = Act::factory()->create([
            'base_price' => 10_000,
            'insured_price' => null,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit);

        $this->assertSame(10_000, $invoice->patient_amount);
        $this->assertSame(0, $invoice->insurance_amount);
    }

    public function test_stay_is_never_covered_by_the_moderator_ticket(): void
    {
        $company = InsuranceCompany::factory()->create(['rate' => 20]);
        $patient = Patient::factory()->create(['insurance_company_id' => $company->id]);

        $act = Act::factory()->create([
            'base_price' => 10_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => now()->subDay(),
        ]);

        $room = Room::factory()->create(['daily_rate' => 20_000]);
        $admission = Admission::factory()->forPatient($patient)->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'admitted_at' => now(),
        ]);
        $admission->syncDayLines();

        $invoice = Invoice::forVisit($visit);

        $this->assertEquals(20, $invoice->ticket_moderateur);

        // Une journée à 20 000 est due en totalité, et le taux ne porte que
        // sur les actes : 20 % de 10 000, soit 2 000.
        $this->assertSame(22_000, $invoice->patient_amount);
        $this->assertSame(8_000, $invoice->insurance_amount);
    }

    public function test_exam_price_is_preserved_on_the_exam(): void
    {
        $patient = Patient::factory()->create();

        $exam = Exam::create([
            'patient_id' => $patient->id,
            'name' => 'NFS',
            'price' => 7_500,
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
        ]);

        $this->assertSame(7_500, $exam->fresh()->price);
    }

    /**
     * Un examen est facturé sur la visite qui l'a demandé. Deux consultations
     * chez le même patient ne doivent jamais se facturer mutuellement.
     */
    public function test_exams_are_billed_on_the_visit_that_requested_them(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        // L'acte est neutralisé : seule la ligne laboratoire doit varier.
        $firstVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now()->subDays(2),
        ]);

        $secondVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now(),
        ]);

        $patient->exams()->create([
            'visit_id' => $firstVisit->id,
            'name' => 'NFS',
            'price' => 5_000,
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now()->subDays(2),
        ]);

        $patient->exams()->create([
            'visit_id' => $secondVisit->id,
            'name' => 'Glycémie',
            'price' => 3_000,
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
        ]);

        $laboratory = ActCategory::Laboratoire->value;

        $firstInvoice = Invoice::forVisit($firstVisit->fresh());
        $this->assertSame(5_000, $firstInvoice->breakdown[$laboratory] ?? null, 'Seul l\'examen de la première visite est facturé.');

        $secondInvoice = Invoice::forVisit($secondVisit->fresh());
        $this->assertSame(3_000, $secondInvoice->breakdown[$laboratory] ?? null, 'Seul l\'examen de la seconde visite est facturé.');
    }

    /**
     * Un examen saisi au laboratoire, hors consultation, n'a pas de visite : il
     * reste imputé à la venue en cours plutôt que d'être perdu.
     */
    public function test_an_exam_without_a_visit_stays_billed_on_the_current_one(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now(),
        ]);

        $patient->exams()->create([
            'visit_id' => null,
            'name' => 'Radio thorax',
            'price' => 12_000,
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());

        $this->assertSame(12_000, $invoice->breakdown[ActCategory::Laboratoire->value] ?? null);
    }
}
