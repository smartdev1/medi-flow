<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Visit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie les 5 invariants du parcours d'admission.
 *
 * 1. Aucune perte de patient   — la demande et la visite restent liées
 * 2. Toute admission est facturée — lignes de séjour dès l'entrée
 * 3. La sortie est un processus  — planifiée puis effective
 * 4. Aucune clôture sans paiement — verrou sur la clôture du dossier
 * 5. Chaque état est ramifiable  — retour en consultation après observation
 */
class AdmissionInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les journées d'hospitalisation sont comptées en jours civils. Sans
        // horloge figée, un test lancé à cheval sur minuit compterait une
        // journée de plus ou de moins selon l'instant exact de son exécution.
        $this->travelTo(Carbon::parse('2026-06-15 10:00:00'));
    }

    private function doctor(): User
    {
        return User::factory()->create([
            'role' => Role::Medecin->value,
            'email_verified_at' => now(),
        ]);
    }

    private function aide(): User
    {
        return User::factory()->create([
            'role' => Role::AideSoignante->value,
            'email_verified_at' => now(),
        ]);
    }

    /** @return array{0: Patient, 1: Visit, 2: AdmissionRequest} */
    private function pendingRequest(string $type, Visit $visit, Patient $patient): AdmissionRequest
    {
        return AdmissionRequest::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'requested_by' => $this->doctor()->id,
            'type' => $type,
            'reason' => 'Test',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);
    }

    /**
     * INVARIANT 1 — la demande approuvée bascule la visite et la porte
     * explicitement dans l'unité correspondante.
     */
    public function test_approved_request_moves_visit_to_the_right_unit(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $this->actingAs($this->aide())
            ->post(route('admission-requests.approve', $this->pendingRequest('observation', $visit, $patient)), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ])
            ->assertRedirect(route('admission-requests.index'));

        $this->assertSame(Visit::STATUS_OBSERVATION, $visit->fresh()->status);

        $admission = Admission::where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame(Admission::STATUS_OBSERVATION, $admission->status);
        $this->assertSame($visit->id, $admission->visit_id, 'L\'admission doit être rattachée à sa visite.');
    }

    /**
     * INVARIANT 1 — un rejet ramène la visite en consultation : pas de
     * cul-de-sac.
     */
    public function test_rejected_request_returns_visit_to_consultation(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $this->actingAs($this->aide())
            ->post(route('admission-requests.reject', $this->pendingRequest('observation', $visit, $patient)));

        $this->assertSame(Visit::STATUS_IN_CONSULTATION, $visit->fresh()->status);
        $this->assertDatabaseMissing('admissions', ['patient_id' => $patient->id]);
    }

    /**
     * INVARIANT 2 — la journée d'admission est facturée dès l'entrée, sans
     * attendre la sortie.
     */
    public function test_admission_bills_the_first_day_immediately(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $this->actingAs($this->aide())
            ->post(route('admission-requests.approve', $this->pendingRequest('hospitalisation', $visit, $patient)), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ]);

        $admission = Admission::where('patient_id', $patient->id)->firstOrFail();
        $this->assertCount(1, $admission->dayLines, 'La journée d\'admission doit être facturée.');
        $this->assertSame(10_000, (int) $admission->dayLines->first()->amount);

        $invoice = Invoice::where('visit_id', $visit->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(10_000, $invoice->subtotal, 'La facture doit inclure la journée.');
    }

    /**
     * INVARIANT 2 — un séjour de plusieurs jours est facturé jour par jour au
     * tarif de la chambre, et non au tarif figé du modèle.
     */
    public function test_stay_is_billed_per_day_at_the_room_rate(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now()->subDays(2),
        ]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 25_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now()->subDays(2),
        ]);

        $admission->syncDayLines();
        $this->assertCount(3, $admission->fresh()->dayLines, 'Trois journées doivent être facturées.');
        $this->assertSame(25_000, (int) $admission->dayLines->first()->amount);

        // La facture agrège la consultation ET le séjour : on vérifie la part
        // hospitalisation pour ne pas dépendre du prix de l'acte. L'acte est
        // neutralisé car la fabrique en tire une catégorie au hasard, qui
        // pourrait être « hospitalisation » et polluer cette même ligne.
        $invoice = Invoice::forVisit($visit->fresh());
        $this->assertSame(75_000, $invoice->breakdown['hospitalisation'] ?? null);
    }

    /**
     * Un séjour encore en cours se facture jusqu'à aujourd'hui, sans qu'aucune
     * action manuelle ne soit nécessaire : la facture suit le même calendrier
     * que le montant affiché à l'accueil.
     */
    public function test_an_ongoing_stay_keeps_billing_up_to_today_without_a_manual_sync(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => null,
            'arrived_at' => now()->subDays(2),
        ]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 25_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now()->subDays(2),
        ]);

        // Aucune ligne de facturation n'a été générée depuis l'admission : c'est
        // le recalcul de la facture qui doit rattraper les journées manquantes.
        $this->assertCount(0, $admission->dayLines);

        $invoice = Invoice::forVisit($visit->fresh());

        $this->assertCount(3, $admission->fresh()->dayLines, 'Les trois journées doivent être facturées.');
        $this->assertSame(75_000, $invoice->breakdown['hospitalisation'] ?? null);
    }

    /**
     * La synchronisation ne doit rien casser quand elle est appelée plusieurs
     * fois : le prix de la journée reste figé et le nombre de lignes stable.
     */
    public function test_syncing_day_lines_is_idempotent(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 25_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        $admission->syncDayLines();
        $firstPass = $admission->fresh()->dayLines->map(fn ($line) => [
            'day_date' => $line->day_date->toDateString(),
            'amount' => (int) $line->amount,
        ])->all();

        // Une hausse du tarif de la chambre ne doit pas modifier le séjour.
        $room->update(['daily_rate' => 40_000]);

        $admission->syncDayLines();
        $secondPass = $admission->fresh()->dayLines->map(fn ($line) => [
            'day_date' => $line->day_date->toDateString(),
            'amount' => (int) $line->amount,
        ])->all();

        $this->assertSame($firstPass, $secondPass);
    }

    /**
     * INVARIANT 3 — la sortie se fait en deux temps : planifier ne libère
     * pas le patient.
     */
    public function test_planned_discharge_does_not_release_the_patient(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->doctor())
            ->post(route('admissions.plan-discharge', $admission));

        $admission->refresh();
        $this->assertSame(Admission::STATUS_DISCHARGE_PLANNED, $admission->status);
        $this->assertNull($admission->discharged_at, 'Planifier ne doit pas libérer le patient.');
    }

    public function test_planned_discharge_can_be_cancelled(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);

        $admission->planDischarge();
        $this->actingAs($this->doctor())->post(route('admissions.cancel-discharge', $admission));

        $this->assertSame(Admission::STATUS_STABLE, $admission->fresh()->status);
    }

    /**
     * INVARIANT 3 + 2 — la sortie effective clôture le séjour et fige les
     * lignes de facturation.
     */
    public function test_discharge_closes_the_stay_and_freezes_billing(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'arrived_at' => now()->subDays(1),
        ]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now()->subDays(1),
        ]);
        $admission->syncDayLines();

        $this->actingAs($this->doctor())->post(route('admissions.discharge', $admission));

        $admission->refresh();
        $this->assertNotNull($admission->discharged_at);
        $this->assertCount(2, $admission->dayLines);

        $this->assertSame(Visit::STATUS_CONSULTATION_CLOSED, $visit->fresh()->status);
    }

    /**
     * INVARIANT 5 — la sortie d'observation ramène le patient en consultation.
     */
    public function test_observation_discharge_returns_visit_to_consultation(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_OBSERVATION,
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->doctor())->post(route('admissions.discharge', $admission));

        $this->assertSame(
            Visit::STATUS_RETURNED_FROM_OBSERVATION,
            $visit->fresh()->status,
            'Un patient sorti d\'observation doit revenir en consultation.'
        );
    }

    /**
     * INVARIANT 5 — une sortie planifiée en observation conserve l'unité
     * d'origine, y compris après annulation.
     */
    public function test_planned_observation_discharge_keeps_its_unit(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_OBSERVATION,
            'admitted_at' => now(),
        ]);
        $visit->update(['status' => Visit::STATUS_OBSERVATION]);

        $admission->planDischarge();
        $this->assertSame(Visit::STATUS_OBSERVATION, $admission->unit());

        $admission->cancelPlannedDischarge();
        $this->assertSame(Admission::STATUS_OBSERVATION, $admission->fresh()->status);
    }

    /**
     * Séparation des unités : un patient en observation n'apparaît pas dans
     * la liste d'hospitalisation.
     */
    public function test_units_do_not_overlap(): void
    {
        $doctor = $this->doctor();

        $observationPatient = Patient::factory()->create(['last_name' => 'SieurObservation']);
        $observationVisit = Visit::factory()->inConsultation()->create(['patient_id' => $observationPatient->id]);
        $observationRoom = Room::factory()->create(['capacity' => 2]);
        $observationPatient->admissions()->create([
            'visit_id' => $observationVisit->id,
            'room_id' => $observationRoom->id,
            'bed_number' => 1,
            'reason' => 'Motif observation',
            'status' => Admission::STATUS_OBSERVATION,
            'admitted_at' => now(),
        ]);
        $observationVisit->update(['status' => Visit::STATUS_OBSERVATION]);

        $hospPatient = Patient::factory()->create(['last_name' => 'SieurHospitalise']);
        $hospVisit = Visit::factory()->inConsultation()->create(['patient_id' => $hospPatient->id]);
        $hospRoom = Room::factory()->create(['capacity' => 2]);
        $hospPatient->admissions()->create([
            'visit_id' => $hospVisit->id,
            'room_id' => $hospRoom->id,
            'bed_number' => 1,
            'reason' => 'Motif hospitalisation',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);
        $hospVisit->update(['status' => Visit::STATUS_HOSPITALIZED]);

        $hospitalisation = $this->actingAs($doctor)->get(route('hospitalisation.index'));
        $hospitalisation->assertOk();
        $this->assertSeeInUnitTable($hospitalisation, 'SieurHospitalise');
        $this->assertNotSeeInUnitTable($hospitalisation, 'SieurObservation');

        $observation = $this->actingAs($doctor)->get(route('observation.index'));
        $observation->assertOk();
        $this->assertSeeInUnitTable($observation, 'SieurObservation');
        $this->assertNotSeeInUnitTable($observation, 'SieurHospitalise');
    }

    /**
     * Vérifie la présence d'un nom dans le tableau de l'unité, et non dans la
     * page entière : le sélecteur de patient embarque la liste de tous les
     * patients en JSON.
     */
    private function assertSeeInUnitTable($response, string $name): void
    {
        $response->assertSee('<td class="px-5 py-3.5">', false);
        $this->assertStringContainsString($name, $this->tableMarkup($response), $name.' devrait figurer dans le tableau.');
    }

    private function assertNotSeeInUnitTable($response, string $name): void
    {
        $this->assertStringNotContainsString($name, $this->tableMarkup($response), $name.' ne devrait pas figurer dans le tableau.');
    }

    private function tableMarkup($response): string
    {
        $content = $response->getContent();
        $start = strpos($content, '<tbody');
        $end = strpos($content, '</tbody>');

        return $start !== false && $end !== false
            ? substr($content, $start, $end - $start)
            : '';
    }

    /**
     * INVARIANT 4 — la sortie médicale n'encaisse rien : seule la clôture du
     * dossier est conditionnée au paiement.
     */
    public function test_medical_discharge_is_allowed_before_payment(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $room = Room::factory()->create(['capacity' => 2, 'daily_rate' => 10_000]);

        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => Admission::STATUS_STABLE,
            'admitted_at' => now(),
        ]);
        $admission->syncDayLines();
        Invoice::forVisit($visit);

        $this->assertNotSame(
            Invoice::STATUS_PAID,
            Invoice::where('visit_id', $visit->id)->first()->status
        );

        $this->actingAs($this->doctor())
            ->post(route('admissions.discharge', $admission))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($admission->fresh()->discharged_at);
    }

    /**
     * INVARIANT 4 — la clôture du dossier reste bloquée tant que la facture
     * n'est pas soldée.
     */
    public function test_dossier_closure_is_blocked_until_payment(): void
    {
        $supervisor = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        Invoice::factory()->pending()->withAmounts(50_000)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $this->actingAs($supervisor)
            ->post(route('sortie.close', $visit))
            ->assertSessionHas('error');

        $this->assertNull($visit->fresh()->discharged_at, 'Le dossier ne doit pas être clôturé.');

        Invoice::where('visit_id', $visit->id)->first()->update(['status' => Invoice::STATUS_PAID]);

        $this->actingAs($supervisor)->post(route('sortie.close', $visit));

        $this->assertNotNull($visit->fresh()->discharged_at);
    }

    /**
     * Garde-fou : deux admissions simultanées sur la même visite sont
     * refusées, et le type « hospitalization » (anglais, historique) est
     * interprété comme une hospitalisation.
     */
    public function test_legacy_english_type_is_normalised(): void
    {
        $request = AdmissionRequest::create([
            'patient_id' => Patient::factory()->create()->id,
            'visit_id' => Visit::factory()->create()->id,
            'requested_by' => $this->doctor()->id,
            'type' => 'hospitalization',
            'reason' => 'Ancienne donnée',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        $this->assertSame('hospitalisation', $request->type);
        $this->assertFalse($request->isObservation());
    }
}
