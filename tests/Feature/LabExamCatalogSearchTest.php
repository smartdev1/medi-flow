<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\LabExam;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le médecin compose le libellé de l'examen et le catalogue tarifé est interrogé
 * en base dès la première frappe.
 *
 * Un examen absent du catalogue reste saisissable, mais son prix doit alors être
 * fourni : un examen consommé sans prix ne serait jamais facturé.
 */
class LabExamCatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(): User
    {
        return User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);
    }

    public function test_the_search_returns_catalog_exams_matching_the_typed_text(): void
    {
        LabExam::create(['name' => 'Glycémie à jeun', 'category' => 'Biochimie', 'price' => 3_000]);
        LabExam::create(['name' => 'Numération Formule Sanguine', 'category' => 'Hématologie', 'price' => 5_000]);

        $response = $this->actingAs($this->doctor())
            ->getJson(route('exams.search', ['q' => 'glyc']));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Glycémie à jeun', 'category' => 'Biochimie']);
    }

    public function test_the_search_also_matches_the_category(): void
    {
        LabExam::create(['name' => 'CRP', 'category' => 'Biochimie', 'price' => 4_000]);

        $response = $this->actingAs($this->doctor())
            ->getJson(route('exams.search', ['q' => 'Biochimie']));

        $response->assertOk()->assertJsonCount(1);
    }

public function test_the_search_never_exposes_a_price(): void
    {
        LabExam::create(['name' => 'Bilan hépatique', 'category' => 'Biochimie', 'price' => 10_000]);

        // Le médecin ne chiffre pas l'analyse : le catalogue reste la seule
        // source de tarif, et le navigateur du prescripteur n'en a pas besoin.
        $response = $this->actingAs($this->doctor())
            ->getJson(route('exams.search', ['q' => 'HÉPA']));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => 1, 'name' => 'Bilan hépatique', 'category' => 'Biochimie']);

        $this->assertArrayNotHasKey(
            'price',
            $response->json('0'),
            'Le prix ne doit pas être renvoyé au prescripteur.'
        );
        $this->assertArrayNotHasKey('formatted_price', $response->json('0'));
    }

    public function test_an_empty_query_returns_nothing(): void
    {
        LabExam::create(['name' => 'Glycémie à jeun', 'category' => 'Biochimie', 'price' => 3_000]);

        $this->actingAs($this->doctor())
            ->getJson(route('exams.search', ['q' => '']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_the_search_is_limited_to_a_reasonable_number_of_results(): void
    {
        foreach (range(1, 25) as $index) {
            LabExam::create([
                'name' => 'Examen de contrôle '.$index,
                'category' => 'Divers',
                'price' => 1_000 * $index,
            ]);
        }

        $this->actingAs($this->doctor())
            ->getJson(route('exams.search', ['q' => 'contrôle']))
            ->assertOk()
            ->assertJsonCount(10);
    }

    public function test_the_search_requires_authentication(): void
    {
        $this->getJson(route('exams.search', ['q' => 'glyc']))->assertUnauthorized();
    }

    public function test_the_search_is_refused_to_a_role_that_cannot_request_an_exam(): void
    {
        $caissier = User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($caissier)
            ->getJson(route('exams.search', ['q' => 'glyc']))
            ->assertForbidden();
    }

    public function test_a_catalog_exam_is_billed_at_its_catalog_price(): void
    {
        $glycemie = LabExam::create([
            'name' => 'Glycémie à jeun',
            'category' => 'Biochimie',
            'price' => 3_000,
        ]);

        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => $glycemie->id, 'name' => 'Glycémie à jeun'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(3_000, Exam::where('name', 'Glycémie à jeun')->firstOrFail()->price);
        $this->assertSame(3_000, (int) Invoice::forVisit($visit->fresh())->breakdown['laboratoire']);
    }

    public function test_several_exams_can_be_requested_in_one_go(): void
    {
        $glycemie = LabExam::create(['name' => 'Glycémie', 'category' => 'Biochimie', 'price' => 3_000]);
        $lipidique = LabExam::create(['name' => 'Bilan lipidique', 'category' => 'Biochimie', 'price' => 8_000]);

        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => $glycemie->id, 'name' => 'Glycémie'],
                    ['lab_exam_id' => $lipidique->id, 'name' => 'Bilan lipidique'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Exam::where('visit_id', $visit->id)->count());

        // Les deux examens sont facturés ensemble, sur la même facture.
        $this->assertSame(
            11_000,
            (int) Invoice::forVisit($visit->fresh())->breakdown['laboratoire']
        );
    }

    public function test_the_price_comes_from_the_catalogue_and_not_from_the_request(): void
    {
        $glycemie = LabExam::create(['name' => 'Glycémie', 'category' => 'Biochimie', 'price' => 3_000]);

        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        // Un prix injecté dans la requête ne doit rien changer : le catalogue
        // reste la seule source de tarif.
        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => $glycemie->id, 'name' => 'Glycémie', 'price' => 1],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(3_000, Exam::where('visit_id', $visit->id)->firstOrFail()->price);
    }

    public function test_the_name_recorded_is_the_catalogue_one(): void
    {
        $glycemie = LabExam::create(['name' => 'Glycémie à jeun', 'category' => 'Biochimie', 'price' => 3_000]);

        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => $glycemie->id, 'name' => 'glycemie bidon'],
                ],
            ])
            ->assertSessionHasNoErrors();

        // L'examen demandé et l'examen facturé doivent porter le même nom.
        $this->assertSame('Glycémie à jeun', Exam::where('visit_id', $visit->id)->firstOrFail()->name);
    }

    public function test_an_exam_outside_the_catalog_is_refused(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        // Sans tarif de référence, l'examen serait consommé au laboratoire sans
        // jamais être facturé.
        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => 999_999, 'name' => 'Examen non catalogué'],
                ],
            ])
            ->assertSessionHasErrors('items.0.lab_exam_id');

        $this->assertSame(0, Exam::where('name', 'Examen non catalogué')->count());
    }

    public function test_a_request_without_any_exam_is_refused(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Exam::count());
    }

    public function test_an_exam_request_without_a_price_is_still_accepted(): void
    {
        $glycemie = LabExam::create(['name' => 'Glycémie', 'category' => 'Biochimie', 'price' => 3_000]);

        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        // Le prix est désormais facultatif dans la charge utile : c'est le
        // catalogue qui le fournit.
        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => $glycemie->id, 'name' => 'Glycémie'],
                ],
            ])
            ->assertSessionHasNoErrors();
    }
}

