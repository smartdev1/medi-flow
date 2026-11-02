<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Act;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionTransfer;
use App\Models\User;
use App\Models\Versement;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiabilit�� de l'encaissement et modèle par vacation.
 *
 * Deux règles sont vérifiées ici parce que leur violation rend les statistiques
 * de caisse fausses sans aucun signe visible :
 *   1. aucun encaissement hors vacation, quel que soit le chemin emprunté ;
 *   2. aucun encaissement supérieur au solde restant du dossier.
 */
class CashierIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        return User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);
    }

    private function insuredPatient(int $insuranceCompanyId): Patient
    {
        return Patient::factory()->create([
            'insurance_company_id' => $insuranceCompanyId,
        ]);
    }

    /**
     * Une consultation dont l'acte a un prix connu : `Invoice::recalculate()`
     * relit le prix de l'acte, il ne faut donc pas laisser la factory tirer
     * un montant aléatoire sous les assertions.
     */
    private function visitPricedAt(Patient $patient, int $price): Visit
    {
        $act = Act::factory()->create([
            'base_price' => $price,
            'insured_price' => null,
        ]);

        return Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => $act->id,
        ]);
    }

    public function test_the_basket_page_refuses_a_payment_without_an_open_session(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::forVisit($visit);

        // Ce que fait réellement le caissier sur la page : montant et mode de
        // paiement sont désormais proposés par le formulaire.
        $response = $this->actingAs($cashier)
            ->from(route('caisse.paniers.show', $invoice))
            ->post(route('caisse.paniers.pay', $invoice), [
                'amount' => 10_000,
                'method' => 'Espèces',
            ]);

        $response->assertRedirect(route('caisse.paniers.show', $invoice));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
        $this->assertFalse($invoice->fresh()->isPaid());

        // L'absence de session est annoncée sur la page avant même le clic.
        $this->actingAs($cashier)
            ->get(route('caisse.paniers.show', $invoice))
            ->assertOk()
            ->assertSee('Aucune session de caisse ouverte');
    }

    public function test_the_basket_page_reports_a_rejected_payment_in_the_page_body(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::forVisit($visit);

        // Un montant supérieur au solde restant est refusé par la validation.
        // Le refus doit être affiché, sinon le caissier voit une page identique.
        $this->actingAs($cashier)
            ->from(route('caisse.paniers.show', $invoice))
            ->post(route('caisse.paniers.pay', $invoice), [
                'amount' => 99_000,
                'method' => 'Espèces',
            ])
            ->assertRedirect(route('caisse.paniers.show', $invoice))
            ->assertSessionHasErrors('amount');

        $this->actingAs($cashier)
            ->get(route('caisse.paniers.show', $invoice))
            ->assertOk()
            ->assertSee("Le formulaire n'a pas pu", false)
            ->assertSee('name="method"', false)
            ->assertSee('name="amount"', false);
    }

    public function test_paying_an_invoice_requires_an_open_session(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::factory()->awaitingPayment()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        $this->actingAs($cashier)
            ->post(route('caisse.paniers.pay', $invoice), ['method' => 'Espèces'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
        $this->assertFalse($invoice->fresh()->isPaid());
    }

    /**
     * Les écrans de la caisse doivent rester rendus : la logique d'encaissement
     * était couverte par ses POST, mais aucune page ne l'était — une relation
     * manquante sur Visit a ainsi pu casser l'affichage sans qu'aucun test ne
     * le remarque.
     */
    public function test_the_cashier_screens_render(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::forVisit($visit);

        $this->actingAs($cashier)->get(route('caisse.index'))->assertOk();
        $this->actingAs($cashier)->get(route('caisse.paniers.show', $invoice))->assertOk();
        $this->actingAs($cashier)->get(route('caisse.jour', today()->toDateString()))->assertOk();
    }

    /**
     * La page d'un dossier soldé doit trouver son règlement : c'est le seul
     * endroit où l'encaissement d'une visite est remonté à l'affichage.
     */
    public function test_a_settled_invoice_screen_shows_its_payment(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::forVisit($visit);

        $this->actingAs($cashier)->post(route('caisse.paniers.pay', $invoice), [
            'method' => 'Espèces',
        ])->assertSessionHas('success');

        $this->assertTrue($invoice->fresh()->isPaid());

        $this->actingAs($cashier)
            ->get(route('caisse.paniers.show', $invoice))
            ->assertOk();
    }

    public function test_paying_an_invoice_records_a_session_bound_payment(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 10_000,
        ]);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        $this->actingAs($cashier)
            ->post(route('caisse.paniers.pay', $invoice), ['method' => 'Espèces'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10_000,
            'cashier_id' => $cashier->id,
            'cashier_session_id' => $session->id,
        ]);

        $this->assertTrue($invoice->fresh()->isPaid());
        $this->assertSame(10_000, (int) $session->settledAmount());
    }

    public function test_partial_payment_is_allowed_and_keeps_the_balance(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        $this->actingAs($cashier)->post(route('caisse.paniers.pay', $invoice), [
            'amount' => 4_000,
            'method' => 'Espèces',
        ]);

        $invoice->refresh();
        $this->assertSame(4_000, (int) $invoice->paid_amount);
        $this->assertSame(6_000, $invoice->remaining_amount);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->status);

        // Le solde se règle ensuite, ce qui solde la facture.
        $this->actingAs($cashier)->post(route('caisse.paniers.pay', $invoice), [
            'amount' => 6_000,
            'method' => 'Espèces',
        ]);

        $this->assertTrue($invoice->fresh()->isPaid());
        $this->assertSame(10_000, (int) $invoice->fresh()->paid_amount);
    }

    public function test_payment_above_the_remaining_balance_is_refused(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $patient = Patient::factory()->uninsured()->create();
        $visit = $this->visitPricedAt($patient, 10_000);
        $invoice = Invoice::factory()->awaitingPayment()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        // Double garde-fou : la validation borne le montant au solde, et
        // Invoice::settle() refuse de nouveau au cas où le solde aurait changé
        // entre la validation et l'enregistrement.
        $this->actingAs($cashier)
            ->post(route('caisse.paniers.pay', $invoice), [
                'amount' => 25_000,
                'method' => 'Espèces',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
        $this->assertFalse($invoice->fresh()->isPaid());
        $this->assertSame(10_000, (int) $invoice->fresh()->patient_amount);
    }

    public function test_ticket_moderateur_applies_to_acts_only_not_to_the_stay(): void
    {
        $company = \App\Models\InsuranceCompany::factory()->create();

        $patient = $this->insuredPatient($company->id);
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'arrived_at' => now()->subDays(2),
            'act_id' => Act::factory()->create([
                'base_price' => 10_000,
                'insured_price' => null,
            ]),
        ]);

        // Séjour de deux journées à 25 000.
        $room = \App\Models\Room::factory()->create(['capacity' => 2, 'daily_rate' => 25_000]);
        $admission = $patient->admissions()->create([
            'visit_id' => $visit->id,
            'room_id' => $room->id,
            'bed_number' => 1,
            'reason' => 'Test',
            'status' => \App\Models\Admission::STATUS_STABLE,
            'admitted_at' => now()->subDays(1),
        ]);
        $admission->syncDayLines();

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->update(['ticket_moderateur' => 20]);
        $invoice->recalculate();
        $invoice->refresh();

        $this->assertSame(60_000, $invoice->subtotal, 'Actes 10 000 + 2 journées à 25 000.');

        // Le taux ne porte que sur les actes : 20 % de 10 000 = 2 000, et le
        // séjour de 50 000 reste entièrement à charge du patient.
        $this->assertSame(52_000, $invoice->patient_amount);
        $this->assertSame(8_000, $invoice->insurance_amount);
    }

    public function test_uninsured_patient_pays_everything(): void
    {
        $patient = Patient::factory()->create(['insurance_company_id' => null]);
        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'act_id' => Act::factory()->create(['base_price' => 10_000, 'insured_price' => null]),
        ]);

        $invoice = Invoice::forVisit($visit->fresh());
        $invoice->update(['ticket_moderateur' => 20]);
        $invoice->recalculate();
        $invoice->refresh();

        $this->assertSame(0, $invoice->insurance_amount, 'Un patient non assuré ne bénéficie d\'aucune prise en charge.');
        $this->assertSame($invoice->subtotal, $invoice->patient_amount);
        $this->assertSame(10_000, $invoice->patient_amount);
    }

    public function test_cashier_session_variance_ignores_voided_payments(): void
    {
        $cashier = $this->cashier();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 10_000,
        ]);

        Payment::factory()->paid()->create([
            'cashier_id' => $cashier->id,
            'cashier_session_id' => $session->id,
            'amount' => 5_000,
        ]);

        Payment::factory()->create([
            'cashier_id' => $cashier->id,
            'cashier_session_id' => $session->id,
            'status' => Payment::STATUS_CANCELLED,
            'amount' => 9_000,
        ]);

        $session->update(['closed_at' => now(), 'closing_balance' => 15_000]);

        $this->assertSame(5_000, $session->settledAmount());
        $this->assertSame(0, $session->cashVariance());
    }

    public function test_handover_moves_the_fund_to_the_next_shift(): void
    {
        $outgoing = $this->cashier();
        $incoming = $this->cashier();

        $session = CashierSession::factory()->closed(closingBalance: 30_000)->create([
            'cashier_id' => $outgoing->id,
            'opening_balance' => 10_000,
        ]);

        $this->actingAs($outgoing)
            ->post(route('caisse.sessions.handover', $session), [
                'to_cashier_id' => $incoming->id,
                'amount' => 20_000,
            ])
            ->assertSessionHas('success');

        $transfer = SessionTransfer::firstOrFail();

        $this->assertSame(20_000, $transfer->amount);
        $this->assertSame($outgoing->id, $transfer->from_cashier_id);
        $this->assertSame($incoming->id, $transfer->to_cashier_id);
        $this->assertSame(10_000, $session->availableForRemittance());
    }

    public function test_handover_cannot_exceed_the_counted_cash(): void
    {
        $outgoing = $this->cashier();
        $incoming = $this->cashier();

        $session = CashierSession::factory()->closed(closingBalance: 30_000)->create([
            'cashier_id' => $outgoing->id,
        ]);

        $this->actingAs($outgoing)
            ->post(route('caisse.sessions.handover', $session), [
                'to_cashier_id' => $incoming->id,
                'amount' => 50_000,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('session_transfers', 0);
    }

    public function test_remittance_is_recorded_against_the_session(): void
    {
        $cashier = $this->cashier();
        $session = CashierSession::factory()->closed(closingBalance: 40_000)->create([
            'cashier_id' => $cashier->id,
        ]);

        $this->actingAs($cashier)
            ->post(route('caisse.versement'), [
                'session_id' => $session->id,
                'amount' => 40_000,
                'method' => 'Espèces',
            ])
            ->assertSessionHas('success');

        $versement = Versement::firstOrFail();

        $this->assertSame(40_000, $versement->amount);
        $this->assertTrue($versement->sessions->contains($session));
        $this->assertSame(0, $session->fresh()->availableForRemittance());
    }

    public function test_remittance_requires_a_closed_session(): void
    {
        $cashier = $this->cashier();
        $session = CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->post(route('caisse.versement'), [
                'session_id' => $session->id,
                'amount' => 10_000,
                'method' => 'Espèces',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('versements', 0);
    }
}
