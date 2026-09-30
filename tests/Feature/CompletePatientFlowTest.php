<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Act;
use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletePatientFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * End-to-end test: full patient journey from registration to discharge.
     *
     * Flow: Accueil → Salle d'attente → Consultation → (Hospitalisation/Observation) → Sortie → Caisse
     */
    public function test_complete_patient_flow_with_hospitalization(): void
    {
        // 1. Create actors
        $accueil = User::factory()->create(['role' => Role::Accueil->value, 'email_verified_at' => now()]);
        $doctor = User::factory()->create(['role' => Role::Medecin->value, 'email_verified_at' => now()]);
        $aide = User::factory()->create(['role' => Role::AideSoignante->value, 'email_verified_at' => now()]);
        $docteurGeneral = User::factory()->create(['role' => Role::DocteurGeneral->value, 'email_verified_at' => now()]);
        $cashier = User::factory()->create(['role' => Role::Caissier->value, 'email_verified_at' => now()]);

        $act = Act::factory()->create(['base_price' => 10000]);
        $room = Room::factory()->create(['capacity' => 2]);

        // ==========================================
        // ÉTAPE 1: Enregistrement à l'accueil
        // ==========================================
        $response = $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Jean',
                'last_name' => 'Baptiste',
                'sex' => 'M',
                'coverage' => 'Non assuré',
                'act_id' => $act->id,
            ]);

        $response->assertRedirect(route('accueil.index'));
        $response->assertSessionHas('success');

        $patient = Patient::where('first_name', 'Jean')->where('last_name', 'Baptiste')->first();
        $this->assertNotNull($patient, 'Patient should be created');
        $visit = $patient->visits()->first();
        $this->assertNotNull($visit, 'Visit should be created');
        $this->assertSame(Visit::STATUS_WAITING, $visit->status);

        // Invoice created
        $invoice = Invoice::where('visit_id', $visit->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(Invoice::STATUS_PENDING, $invoice->status);

        // ==========================================
        // ÉTAPE 2: Salle d'attente - prise de constantes
        // ==========================================
        $response = $this->actingAs($aide)
            ->post(route('visits.vitals', $visit));

        $response->assertRedirect();
        $visit->refresh();
        $this->assertNotNull($visit->vitals_taken_at);

        // ==========================================
        // ÉTAPE 3: Médecin prend le patient
        // ==========================================
        $response = $this->actingAs($doctor)
            ->post(route('medecin.visits.take', $visit));

        $response->assertRedirect('/medecin?patient='.$visit->id);
        $visit->refresh();
        $this->assertSame(Visit::STATUS_IN_CONSULTATION, $visit->status);

        // ==========================================
        // ÉTAPE 4: Demande d'hospitalisation
        // ==========================================
        $response = $this->actingAs($doctor)
            ->post(route('medecin.visits.hospitalize', $visit));

        $response->assertRedirect(route('medecin.index'));
        $visit->refresh();
        $this->assertSame(Visit::STATUS_HOSPITALIZATION_REQUESTED, $visit->status);

        $this->assertDatabaseHas('admission_requests', [
            'visit_id' => $visit->id,
            'type' => 'hospitalisation',
            'status' => AdmissionRequest::STATUS_PENDING,
        ]);

        // ==========================================
        // ÉTAPE 5: Approbation de l'hospitalisation
        // ==========================================
        $admissionRequest = AdmissionRequest::where('visit_id', $visit->id)->first();

        $response = $this->actingAs($aide)
            ->post(route('admission-requests.approve', $admissionRequest), [
                'room_id' => $room->id,
                'bed_number' => 1,
            ]);

        $response->assertRedirect(route('admission-requests.index'));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertSame(Visit::STATUS_HOSPITALIZED, $visit->status);

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $patient->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'status' => Admission::STATUS_STABLE,
        ]);

        // ==========================================
        // ÉTAPE 6: Sortie de l'hospitalisation
        // ==========================================
        $admission = Admission::where('patient_id', $patient->id)->whereNull('discharged_at')->first();
        $this->assertNotNull($admission);

        $response = $this->actingAs($doctor)
            ->post(route('admissions.discharge', $admission));

        $response->assertRedirect(route('hospitalisation.index'));
        $admission->refresh();
        $this->assertNotNull($admission->discharged_at);

        // ==========================================
        // ÉTAPE 7: Facturation - paiement
        // ==========================================
        $invoice->refresh();
        // Invoice should be recalculated
        $this->assertGreaterThan(0, $invoice->subtotal);

        // Cashier records payment. Aucun encaissement n'existe hors vacation :
        // le caissier doit donc ouvrir sa session de caisse d'abord.
        \App\Models\CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 0,
        ]);

        // Le séjour n'est jamais couvert par le ticket modérateur : le taux
        // saisi est donc nul et le patient règle la totalité du montant.
        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), [
                'patient_id' => $patient->id,
                'service' => 'Hospitalisation',
                'base_amount' => $invoice->patient_amount,
                'taux' => 0,
                'amount' => $invoice->patient_amount,
                'method' => 'Espèces',
                'status' => Payment::STATUS_PAID,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Mark invoice as paid so discharge can proceed
        $invoice->update(['status' => \App\Models\Invoice::STATUS_PAID]);

        $this->assertDatabaseHas('payments', [
            'patient_id' => $patient->id,
            'status' => Payment::STATUS_PAID,
            'cashier_id' => $cashier->id,
        ]);

        // ==========================================
        // ÉTAPE 8: Clôture du dossier
        // ==========================================
        $visit->refresh();
        // Doctor closes consultation first
        $consultation = $visit->consultations()->firstOrCreate(
            ['doctor_id' => $doctor->id],
            ['patient_id' => $patient->id, 'status' => 'En cours', 'started_at' => now()]
        );

        $response = $this->actingAs($doctor)
            ->post(route('consultations.close', $consultation));

        $response->assertRedirect();
        $visit->refresh();
        $this->assertSame(Visit::STATUS_CONSULTATION_CLOSED, $visit->status);

        // Docteur General discharges
        $response = $this->actingAs($docteurGeneral)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }

    public function test_complete_patient_flow_with_laboratory(): void
    {
        $accueil = User::factory()->create(['role' => Role::Accueil->value, 'email_verified_at' => now()]);
        $doctor = User::factory()->create(['role' => Role::Medecin->value, 'email_verified_at' => now()]);
        $laborantin = User::factory()->create(['role' => Role::Laborantin->value, 'email_verified_at' => now()]);

        $act = Act::factory()->create(['base_price' => 5000]);

        // Enregistrement
        $this->actingAs($accueil)
            ->post(route('patients.store'), [
                'first_name' => 'Marie',
                'last_name' => 'Diallo',
                'sex' => 'F',
                'coverage' => 'Non assuré',
                'act_id' => $act->id,
            ]);

        $patient = Patient::where('first_name', 'Marie')->first();
        $this->assertNotNull($patient, 'Patient Marie Diallo should exist');
        $visit = $patient->visits()->first();
        $this->assertNotNull($visit, 'Visit should exist');

        // Prise en consultation
        $consultation = \App\Models\Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'En cours',
            'started_at' => now(),
        ]);
        $this->actingAs($doctor)->post(route('medecin.visits.take', $visit));
        $visit->refresh();
        $this->assertSame(Visit::STATUS_IN_CONSULTATION, $visit->status);

        // Demande d'examen
        $response = $this->actingAs($doctor)
            ->post(route('exams.store'), [
                'patient_id' => $patient->id,
                'name' => 'NFS',
                'price' => 8000,
            ]);

        $response->assertSessionHasNoErrors();

        $exam = \App\Models\Exam::where('patient_id', $patient->id)->first();
        $this->assertNotNull($exam);

        // L'examen est une prestation payable : il doit apparaître sur la
        // facture du dossier, sinon il ne sera jamais encaissé. On vérifie sa
        // ligne de détail, le total incluant par ailleurs l'acte de la visite.
        $this->assertSame(8000, (int) $exam->price);
        $this->assertSame(8000, Invoice::forVisit($visit)->breakdown['laboratoire'] ?? 0);

        // Laborantin envoie le résultat (advance: AWAITING_SAMPLE → IN_PROGRESS)
        $this->actingAs($laborantin)
            ->post(route('exams.advance', $exam));

        $exam->refresh();
        $this->assertSame(\App\Models\Exam::STATUS_IN_PROGRESS, $exam->status);

        // Clôture consultation
        $consultation = \App\Models\Consultation::create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'En cours',
            'started_at' => now(),
        ]);
        $this->actingAs($doctor)->post(route('consultations.close', $consultation));

        $visit->refresh();
        $this->assertSame(Visit::STATUS_CONSULTATION_CLOSED, $visit->status);

        // Invoice should include exam price
        $invoice = Invoice::where('visit_id', $visit->id)->first();
        $invoice->recalculate();
        $this->assertGreaterThanOrEqual(8000, $invoice->fresh()->subtotal);
    }
}
