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
 * Un examen est la conséquence d'une consultation : il doit être rattaché à la
 * visite qui l'a demandé, sinon deux visites d'un même patient se facturent
 * mutuellement.
 *
 * Le prix n'est jamais transmis : il provient du catalogue, seule référence de
 * tarif.
 */
class ExamVisitLinkTest extends TestCase
{
    use RefreshDatabase;

    private function catalogExam(string $name, int $price): LabExam
    {
        return LabExam::create([
            'name' => $name,
            'category' => 'Hématologie',
            'price' => $price,
        ]);
    }

    public function test_an_exam_requested_from_a_consultation_is_attached_to_the_visit(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $nfs = $this->catalogExam('NFS', 7_500);

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
                    ['lab_exam_id' => $nfs->id, 'name' => 'NFS'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $exam = Exam::where('name', 'NFS')->firstOrFail();

        $this->assertSame($visit->id, $exam->visit_id);
        $this->assertSame(7_500, $exam->price);
        $this->assertSame(7_500, (int) Invoice::forVisit($visit->fresh())->breakdown['laboratoire']);
    }

    public function test_an_exam_requested_at_the_laboratory_stays_without_a_visit(): void
    {
        $laborantin = User::factory()->create([
            'role' => Role::Laborantin->value,
            'email_verified_at' => now(),
        ]);

        $radiographie = $this->catalogExam('Radio thorax', 12_000);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
        ]);

        $this->actingAs($laborantin)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'items' => [
                    ['lab_exam_id' => $radiographie->id, 'name' => 'Radio thorax'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(Exam::where('name', 'Radio thorax')->firstOrFail()->visit_id);

        // Sans rattachement, l'examen reste imputé à la venue en cours.
        $this->assertSame(12_000, (int) Invoice::forVisit($visit->fresh())->breakdown['laboratoire']);
    }

    public function test_a_visit_belonging_to_another_patient_is_refused(): void
    {
        $doctor = User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);

        $nfs = $this->catalogExam('NFS', 7_500);

        $patient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();

        $otherVisit = Visit::factory()->inConsultation()->create([
            'patient_id' => $otherPatient->id,
        ]);

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $otherVisit->id,
                'items' => [
                    ['lab_exam_id' => $nfs->id, 'name' => 'NFS'],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(0, Exam::where('name', 'NFS')->count());
    }
}