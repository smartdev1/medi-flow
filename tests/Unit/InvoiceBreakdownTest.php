<?php

namespace Tests\Unit;

use App\Enums\ActCategory;
use App\Models\Act;
use App\Models\Admission;
use App\Models\Exam;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
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
        $company = InsuranceCompany::factory()->create();
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
            'ticket_moderateur' => 20,
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

    /**
     * Le ticket modérateur appartient à la consultation, pas au dossier : deux
     * venues du même patient peuvent relever de deux taux distincts. A vient
     * aujourd'hui à 20 %, puis revient deux semaines plus tard à 10 %.
     */
    public function test_the_ticket_modulator_is_carried_by_the_visit_not_by_the_patient(): void
    {
        $company = InsuranceCompany::factory()->create();
        $patient = Patient::factory()->create(['insurance_company_id' => $company->id]);

        $act = Act::factory()->create([
            'base_price' => 10_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $firstVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => now()->subDays(14),
            'ticket_moderateur' => 20,
        ]);

        $secondVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'arrived_at' => now(),
            'ticket_moderateur' => 10,
        ]);

        $firstInvoice = Invoice::forVisit($firstVisit->fresh());
        $this->assertEquals(20, $firstInvoice->ticket_moderateur);
        $this->assertSame(2_000, $firstInvoice->patient_amount);
        $this->assertSame(8_000, $firstInvoice->insurance_amount);

        $secondInvoice = Invoice::forVisit($secondVisit->fresh());
        $this->assertEquals(10, $secondInvoice->ticket_moderateur);
        $this->assertSame(1_000, $secondInvoice->patient_amount);
        $this->assertSame(9_000, $secondInvoice->insurance_amount);

        // Recalculer la première facture plus tard ne doit rien lui emprunter à
        // la seconde : chacune reste à son taux.
        $firstInvoice->fresh()->recalculate();

        $this->assertEquals(20, $firstInvoice->fresh()->ticket_moderateur);
        $this->assertSame(2_000, $firstInvoice->fresh()->patient_amount);
    }

    /**
     * Une consultation sans taux saisi retombe sur le taux de la clinique, et
     * non sur un zéro qui ferait payer tout au patient.
     */
    public function test_a_visit_without_a_ticket_falls_back_to_the_clinic_rate(): void
    {
        $company = InsuranceCompany::factory()->create();
        $patient = Patient::factory()->create(['insurance_company_id' => $company->id]);

        $act = Act::factory()->create([
            'base_price' => 10_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());

        $this->assertEquals(config('clinic.default_ticket_rate'), $invoice->ticket_moderateur);
        $this->assertSame(2_000, $invoice->patient_amount);
    }

    /**
     * La pharmacie ne suit pas le ticket modérateur : elle reste entièrement à
     * la charge du patient, et l'assurance n'en couvre rien. Elle n'est pas
     * pour autant retirée du montant réclamé.
     */
    public function test_the_pharmacy_is_excluded_from_the_ticket_modulator(): void
    {
        $company = InsuranceCompany::factory()->create();
        $patient = Patient::factory()->create(['insurance_company_id' => $company->id]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'ticket_moderateur' => 20,
        ]);

        $prescription = $patient->prescriptions()->create([
            'visit_id' => $visit->id,
            'requested_at' => now(),
            'stock_status' => 'Disponible',
            'status' => Prescription::STATUS_AWAITING_DISPENSE,
        ]);

        $prescription->items()->create([
            'name' => 'Paracétamol',
            'quantity' => 2,
            'unit_price' => 5_000,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());

        $this->assertSame(10_000, $invoice->subtotal);
        $this->assertSame(10_000, $invoice->patient_amount, 'Aucun ticket ne s\'applique à la pharmacie.');
        $this->assertSame(0, $invoice->insurance_amount);
        $this->assertSame(10_000, $invoice->breakdown[ActCategory::Pharmacie->value] ?? null);
    }

    /**
     * L'exclusion ne porte que sur la pharmacie : les actes et les examens
     * suivent toujours le ticket. Sans ce test, une correction future pourrait
     * sortir toute la base du ticket sans qu'aucun autre ne le remarque.
     */
    public function test_only_the_pharmacy_is_excluded_from_the_ticket_modulator(): void
    {
        $company = InsuranceCompany::factory()->create();
        $patient = Patient::factory()->create(['insurance_company_id' => $company->id]);

        $act = Act::factory()->create([
            'base_price' => 10_000,
            'insured_price' => null,
            'category' => ActCategory::Consultation->value,
        ]);

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
            'ticket_moderateur' => 20,
        ]);

        $prescription = $patient->prescriptions()->create([
            'visit_id' => $visit->id,
            'requested_at' => now(),
            'stock_status' => 'Disponible',
            'status' => Prescription::STATUS_AWAITING_DISPENSE,
        ]);

        $prescription->items()->create([
            'name' => 'Gants nitrile',
            'quantity' => 2,
            'unit_price' => 5_000,
        ]);

        $invoice = Invoice::forVisit($visit->fresh());

        // Le ticket de 20 % ne porte que sur la consultation, pas sur la
        // pharmacie : 2 000 de ticket plus 10 000 de pharmacie.
        $this->assertSame(12_000, $invoice->patient_amount);
        $this->assertSame(20_000, $invoice->subtotal, '10 000 de consultation plus 10 000 de pharmacie.');
        $this->assertSame(8_000, $invoice->insurance_amount);
    }

    /**
     * Une ordonnance appartient à la consultation qui l'a prescrite : celle de
     * la seconde venue ne doit pas remonter sur la facture de la première.
     */
    public function test_a_prescription_is_billed_on_the_visit_that_prescribed_it(): void
    {
        $patient = Patient::factory()->uninsured()->create();

        $firstVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now()->subDays(14),
        ]);

        $secondVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now(),
        ]);

        $later = $patient->prescriptions()->create([
            'visit_id' => $secondVisit->id,
            'requested_at' => now(),
            'stock_status' => 'Disponible',
            'status' => Prescription::STATUS_AWAITING_DISPENSE,
        ]);

        $later->items()->create([
            'name' => 'Amoxicilline',
            'quantity' => 1,
            'unit_price' => 7_000,
        ]);

        $firstInvoice = Invoice::forVisit($firstVisit->fresh());
        $this->assertSame(0, $firstInvoice->subtotal, 'La seconde ordonnance ne remonte pas sur la première facture.');

        $secondInvoice = Invoice::forVisit($secondVisit->fresh());
        $this->assertSame(7_000, $secondInvoice->subtotal);
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
