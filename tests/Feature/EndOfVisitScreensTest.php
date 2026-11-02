<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Act;
use App\Models\Admission;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deux écrans de fin de parcours : encaisser une facture depuis la liste, et
 * retrouver les dossiers prêts à sortir. Aucun des deux n'était couvert, alors
 * que ce sont les deux gestes qui terminent la venue du patient.
 */
class EndOfVisitScreensTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le bouton d'encaissement de la liste envoyait un formulaire vide : la
     * validation exige un mode de paiement, l'encaissement échouait donc
     * toujours sans message apparent.
     */
    public function test_the_cashier_list_offers_a_working_payment_form(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $patient = Patient::factory()->uninsured()->create();
        $invoice = Invoice::forVisit($this->visitPricedAt($patient, 10_000));

        $this->actingAs($cashier)
            ->get(route('caisse.index'))
            ->assertOk()
            ->assertSee(route('caisse.paniers.pay', $invoice->id), false)
            ->assertSee('name="method"', false)
            ->assertSee('name="amount"', false);
    }

    public function test_an_invoice_is_settled_from_the_cashier_list(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 0,
        ]);

        $patient = Patient::factory()->uninsured()->create();
        $invoice = Invoice::forVisit($this->visitPricedAt($patient, 10_000));

        $this->actingAs($cashier)
            ->post(route('caisse.paniers.pay', $invoice), [
                'amount' => 10_000,
                'method' => 'Espèces',
            ])
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('success');

        $this->assertTrue($invoice->fresh()->isPaid());
    }

    public function test_a_partial_settlement_is_possible_from_the_cashier_list(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 0,
        ]);

        $patient = Patient::factory()->uninsured()->create();
        $invoice = Invoice::forVisit($this->visitPricedAt($patient, 10_000));

        $this->actingAs($cashier)
            ->post(route('caisse.paniers.pay', $invoice), [
                'amount' => 4_000,
                'method' => 'Mobile Money',
            ])
            ->assertSessionHas('success');

        $fresh = $invoice->fresh();
        $this->assertTrue($fresh->isPartial());
        $this->assertSame(6_000, $fresh->remaining_amount);
    }

    /**
     * La liste des dossiers prêts à sortir passait par le scope `active` de
     * Visit, qui exclut « Consultation terminée » : la condition de présence
     * et la condition d'absence s'annulaient et la liste restait vide.
     *
     * On vérifie l'état vide affiché, et non l'absence du nom dans la page : la
     * page contient aussi le sélecteur de patients, qui cite tout le monde.
     */
    public function test_a_closed_consultation_appears_as_ready_for_discharge(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $patient = Patient::factory()->create();

        Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        $this->actingAs($user)
            ->get(route('sortie.index'))
            ->assertOk()
            ->assertSee('Patients prêts pour la sortie')
            ->assertSee($patient->full_name)
            ->assertDontSee('Aucun patient prêt pour la sortie');
    }

    public function test_a_visit_still_in_consultation_is_not_ready_for_discharge(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();

        Visit::factory()->inConsultation()->create([
            'patient_id' => Patient::factory()->create()->id,
            'status' => Visit::STATUS_IN_CONSULTATION,
        ]);

        $this->actingAs($user)
            ->get(route('sortie.index'))
            ->assertOk()
            ->assertSee('Aucun patient prêt pour la sortie');
    }

    public function test_an_already_discharged_patient_is_no_longer_listed(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $patient = Patient::factory()->create();

        Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
            'discharged_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('sortie.index'))
            ->assertOk()
            ->assertSee('Aucun patient prêt pour la sortie');
    }

    public function test_a_discharged_stay_also_appears_as_ready_for_discharge(): void
    {
        $user = User::factory()->withRole(Role::Accueil)->create();
        $patient = Patient::factory()->create();

        $visit = Visit::factory()->inConsultation()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_HOSPITALIZED,
        ]);

        // Un séjour sorti médicalement n'est prêt qu'une fois le dépiquage
        // validé par le nursing : tant qu'il ne l'est pas, le dossier figure
        // à l'accueil mais la clôture reste refusée.
        Admission::factory()->forPatient($patient)->create([
            'visit_id' => $visit->id,
            'discharged_at' => now(),
            'depiquage_valide_par' => User::factory()->withRole(Role::AideSoignante)->create()->id,
            'depiquage_valide_le' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('sortie.index'))
            ->assertOk()
            ->assertSee('Patients prêts pour la sortie')
            ->assertSee($patient->full_name);
    }

    private function cashier(): User
    {
        return User::factory()->withRole(Role::Caissier)->create();
    }

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
}
