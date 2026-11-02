<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\LabExam;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un patient orienté vers le laboratoire quitte la file du médecin, et y
 * revient seul une fois le résultat exploitable.
 *
 * Demander un examen ne modifie pas le statut de la visite : sans filtre
 * explicite, le patient restait donc dans la file du médecin alors qu'il
 * figurait déjà dans la colonne Laboratoire du tableau de circuit.
 */
class DoctorQueueTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(): User
    {
        return User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);
    }

    private function visitWithVitals(Patient $patient): Visit
    {
        return Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'vitals_taken_at' => now(),
        ]);
    }

    private function queueNames(User $doctor): array
    {
        return collect($this->actingAs($doctor)->get(route('medecin.index'))->viewData('queue'))
            ->pluck('name')
            ->all();
    }

    public function test_a_patient_sent_to_the_laboratory_leaves_the_doctor_queue(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = $this->visitWithVitals($patient);

        $this->assertContains($patient->full_name, $this->queueNames($doctor));

        $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'items' => [
                    ['lab_exam_id' => LabExam::create([
                        'name' => 'NFS',
                        'category' => 'Hématologie',
                        'price' => 7_500,
                    ])->id, 'name' => 'NFS'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotContains(
            $patient->full_name,
            $this->queueNames($doctor),
            'Un patient orienté vers le laboratoire ne doit pas rester dans la file du médecin.'
        );
    }

    public function test_the_patient_returns_to_the_queue_once_the_result_is_ready(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = $this->visitWithVitals($patient);

        $exam = Exam::factory()->awaitingSample()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        $this->assertNotContains($patient->full_name, $this->queueNames($doctor));

        $exam->update(['status' => Exam::STATUS_IN_PROGRESS]);
        $this->assertNotContains(
            $patient->full_name,
            $this->queueNames($doctor),
            'Un résultat en cours de saisie garde le patient au laboratoire.'
        );

        $exam->update(['status' => Exam::STATUS_READY]);

        $this->assertContains(
            $patient->full_name,
            $this->queueNames($doctor),
            'Le patient doit revenir chez le médecin dès que le résultat est prêt.'
        );
    }

    public function test_the_medical_record_stays_reachable_while_the_patient_is_in_the_laboratory(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = $this->visitWithVitals($patient);

        Exam::factory()->awaitingSample()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        $response = $this->actingAs($doctor)
            ->get(route('medecin.index', ['patient' => $visit->id]));

        $response->assertOk();

        $this->assertNotNull(
            $response->viewData('patient'),
            'Le dossier doit rester accessible par lien direct, même hors file.'
        );
        $this->assertSame($patient->full_name, $response->viewData('patient')['name']);
    }

    public function test_the_queue_and_the_badge_agree_on_the_laboratory_patient(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = $this->visitWithVitals($patient);

        Exam::factory()->awaitingSample()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
        ]);

        $queueSize = count($this->queueNames($doctor));
        $badgeCount = $this->actingAs($doctor)->get('/api/badges')->json('medecin');

        $this->assertSame($queueSize, $badgeCount, 'Le badge doit refléter la file réellement affichée.');
    }

    public function test_the_queue_and_the_badge_agree_on_a_closed_consultation(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();
        $visit = $this->visitWithVitals($patient);

        $visit->consultations()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => \App\Models\Consultation::STATUS_CLOSED,
            'started_at' => now(),
        ]);

        $queueSize = count($this->queueNames($doctor));
        $badgeCount = $this->actingAs($doctor)->get('/api/badges')->json('medecin');

        $this->assertSame(0, $queueSize);
        $this->assertSame($queueSize, $badgeCount);
    }
}
