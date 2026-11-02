<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Point de vente pharmacie.
 *
 * Trois règles y sont verrouillées, chacune parce que sa violation rend le
 * rapprochement faux sans aucun signe visible à l'écran :
 *   1. une seule vacation de point de vente active à la fois ;
 *   2. la vente est possible sans vacation de caisse, et rattachée à la
 *      vacation désignée lorsqu'il y en a une ;
 *   3. les fonds du point de vente ne sont jamais confondus avec ceux de la
 *      caisse.
 */
class PharmacyCashPointTest extends TestCase
{
    use RefreshDatabase;

    private function pharmacist(): User
    {
        return User::factory()->create([
            'role' => Role::Pharmacien->value,
            'email_verified_at' => now(),
        ]);
    }

    private function cashier(): User
    {
        return User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);
    }

    private function openSession(array $attributes = []): CashierSession
    {
        return CashierSession::factory()->create(array_merge([
            'opened_at' => now(),
            'opening_balance' => 0,
        ], $attributes));
    }

    public function test_a_regular_cashier_session_is_not_a_cash_point(): void
    {
        $this->openSession(['cashier_id' => $this->cashier()->id]);

        $this->assertSame(0, CashierSession::cashPoint()->count());
    }

    public function test_a_cash_point_session_is_flagged_and_found_by_scope(): void
    {
        $session = $this->openSession(['cashier_id' => $this->pharmacist()->id]);

        $session->update(['is_cash_point' => true]);

        $this->assertTrue($session->fresh()->is_cash_point);
        $this->assertSame(1, CashierSession::cashPoint()->count());
    }

    public function test_no_open_cash_point_returns_null(): void
    {
        $this->assertNull(app(CashierService::class)->openCashPointSession());
    }

    public function test_the_open_cash_point_is_designated(): void
    {
        $session = $this->openSession(['is_cash_point' => true]);

        $this->assertTrue(app(CashierService::class)->openCashPointSession()?->is($session));
    }

    public function test_a_closed_cash_point_is_not_designated(): void
    {
        $this->openSession(['is_cash_point' => true, 'closed_at' => now()->subHour()]);

        $this->assertNull(app(CashierService::class)->openCashPointSession());
    }

    /**
     * Deux points de vente ouverts signifieraient deux fonds dont personne ne
     * garde la trace. Le plus récent fait foi, l'autre est fermé.
     */
    public function test_only_the_most_recent_cash_point_stays_open(): void
    {
        $older = $this->openSession(['is_cash_point' => true, 'opened_at' => now()->subHours(3)]);
        $newer = $this->openSession(['is_cash_point' => true, 'opened_at' => now()->subHour()]);

        $designated = app(CashierService::class)->openCashPointSession();

        $this->assertTrue($designated?->is($newer));
        $this->assertTrue($older->fresh()->isClosed());
    }

    public function test_a_cashier_session_is_never_closed_by_the_cash_point_designation(): void
    {
        $cashierSession = $this->openSession(['cashier_id' => $this->cashier()->id]);
        $this->openSession(['is_cash_point' => true, 'cashier_id' => $this->pharmacist()->id]);

        app(CashierService::class)->openCashPointSession();

        $this->assertFalse($cashierSession->fresh()->isClosed());
    }

    /**
     * Une vente n'exige pas de vacation de caisse : un patient peut se
     * présenter sans passage par la consultation. Le service ne doit donc pas
     * appeler `requireOpenSession()`, qui l'exigerait.
     */
    public function test_a_pharmacy_sale_succeeds_without_any_cashier_session(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 100,
            'unit_price' => 1_000,
        ]);

        $payment = app(CashierService::class)->recordPharmacySale($pharmacist, [
            'patient_id' => $patient->id,
            'service' => 'Vente pharmacie',
            'amount' => 2_000,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 2],
            ],
        ]);

        $this->assertNull($payment->cashier_session_id, 'Aucune vacation désignée, donc aucun rattachement.');
        $this->assertTrue($payment->cashier->is($pharmacist), 'Le pharmacien est l\'auteur du paiement.');
        $this->assertSame(98, $medication->fresh()->stock_quantity);
    }

    public function test_a_sale_is_attached_to_the_designated_cash_point(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 100,
            'unit_price' => 1_000,
        ]);

        $cashPoint = $this->openSession([
            'is_cash_point' => true,
            'cashier_id' => $pharmacist->id,
        ]);

        $payment = app(CashierService::class)->recordPharmacySale($pharmacist, [
            'patient_id' => $patient->id,
            'service' => 'Vente pharmacie',
            'amount' => 1_000,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($payment->session->is($cashPoint));
        $this->assertSame(1_000, $cashPoint->fresh()->settledAmount());
    }

    /**
     * Le prix vient du catalogue, jamais du client : sans cela, un montant
     * saisi dans le navigateur pourrait être inférieur au coût réel.
     */
    public function test_the_client_price_is_ignored_and_the_catalogue_price_applied(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 100,
            'unit_price' => 1_000,
        ]);

        $payment = app(CashierService::class)->recordPharmacySale($pharmacist, [
            'patient_id' => $patient->id,
            'service' => 'Vente pharmacie',
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 3, 'unit_price' => 1],
            ],
        ]);

        $this->assertSame(3_000, $payment->amount);
    }

    public function test_a_sale_beyond_available_stock_is_refused_and_leaves_no_payment(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 2,
            'unit_price' => 1_000,
        ]);

        $before = Payment::count();

        try {
            app(CashierService::class)->recordPharmacySale($pharmacist, [
                'patient_id' => $patient->id,
                'service' => 'Vente pharmacie',
                'method' => Payment::METHOD_CASH,
                'items' => [
                    ['medication_id' => $medication->id, 'quantity' => 5],
                ],
            ]);
            $this->fail('Une vente au-delà du stock doit être refusée.');
        } catch (\App\Exceptions\PaymentRefusedException) {
            // attendu
        }

        $this->assertSame($before, Payment::count(), 'Aucun paiement orphelin ne doit rester.');
        $this->assertSame(2, $medication->fresh()->stock_quantity, 'Le stock ne doit pas bouger.');
    }

    public function test_a_sale_needs_at_least_one_medication(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $this->expectException(\App\Exceptions\PaymentRefusedException::class);

        app(CashierService::class)->recordPharmacySale($pharmacist, [
            'patient_id' => $patient->id,
            'service' => 'Vente pharmacie',
            'method' => Payment::METHOD_CASH,
            'items' => [],
        ]);
    }

    public function test_a_sale_does_not_carry_the_ticket_modulator(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 100,
            'unit_price' => 1_000,
        ]);

        $payment = app(CashierService::class)->recordPharmacySale($pharmacist, [
            'patient_id' => $patient->id,
            'service' => 'Vente pharmacie',
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(1_000, $payment->base_amount);
        // `payments.taux` est `NOT NULL DEFAULT 0` : l'absence de ticket
        // modérateur s'y inscrit par 0.
        $this->assertEquals(0, (float) $payment->taux);
    }

    /**
     * La recherche du patient est dans le flux de la page : un tiroir la
     * masquerait derrière un clic et ferait perdre au pharmacien la file des
     * ordonnances pendant la saisie.
     */
    public function test_the_patient_search_is_inline_and_not_hidden_in_a_drawer(): void
    {
        $response = $this->actingAs($this->pharmacist())->get(route('pharmacie.index'));

        $response->assertOk();
        $response->assertSee('Vente au comptoir');
        $response->assertSee('id="sale-patient"', escape: false);

        // Le tiroir et son bouton d'ouverture ont disparu : la vente se compose
        // directement sur la page.
        $response->assertDontSee('Nouvelle vente');
    }

    public function test_a_sale_recorded_from_the_screen_decrements_stock_and_pays_the_pharmacist(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 50,
            'unit_price' => 1_000,
        ]);

        $response = $this->actingAs($pharmacist)->post(route('pharmacie.sell'), [
            'patient_id' => $patient->id,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 4],
            ],
        ]);

        $response->assertRedirect(route('pharmacie.index'));
        $response->assertSessionHas('success');

        $payment = Payment::latest('id')->first();
        $this->assertSame(4_000, $payment->amount);
        $this->assertTrue($payment->cashier->is($pharmacist));
        $this->assertSame(46, $medication->fresh()->stock_quantity);
    }

    /**
     * Un prix transmis par le navigateur ne doit jamais être pris en compte.
     */
    public function test_a_price_sent_by_the_browser_is_ignored(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 50,
            'unit_price' => 1_000,
        ]);

        $this->actingAs($pharmacist)->post(route('pharmacie.sell'), [
            'patient_id' => $patient->id,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 2, 'unit_price' => 1],
            ],
        ]);

        $this->assertSame(2_000, Payment::latest('id')->first()->amount);
    }

    public function test_a_sale_beyond_stock_is_refused_from_the_screen(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 2,
            'unit_price' => 1_000,
        ]);

        $this->actingAs($pharmacist)->post(route('pharmacie.sell'), [
            'patient_id' => $patient->id,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 9],
            ],
        ])->assertSessionHas('error');

        $this->assertSame(0, Payment::count());
        $this->assertSame(2, $medication->fresh()->stock_quantity);
    }

    public function test_a_sale_needs_a_registered_patient(): void
    {
        $pharmacist = $this->pharmacist();

        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 50,
            'unit_price' => 1_000,
        ]);

        $this->actingAs($pharmacist)->post(route('pharmacie.sell'), [
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('patient_id');

        $this->assertSame(0, Payment::count());
    }

    public function test_a_sale_submitted_without_any_medication_is_refused(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $this->actingAs($pharmacist)->post(route('pharmacie.sell'), [
            'patient_id' => $patient->id,
            'method' => Payment::METHOD_CASH,
            'items' => [],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, Payment::count());
    }

    public function test_a_sale_is_forbidden_to_a_role_that_is_not_a_pharmacist(): void
    {
        $medication = Medication::create([
            'name' => 'Gants nitrile',
            'stock_quantity' => 50,
            'unit_price' => 1_000,
        ]);

        $this->actingAs($this->cashier())->post(route('pharmacie.sell'), [
            'patient_id' => Patient::factory()->create()->id,
            'method' => Payment::METHOD_CASH,
            'items' => [
                ['medication_id' => $medication->id, 'quantity' => 1],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Payment::count());
    }

    /**
     * La délivrance d'une ordonnance est un autre chemin : elle concerne un
     * patient qui a déjà une prescription, mais ne doit pas être confondue avec
     * la vente libre.
     */
    public function test_dispensing_a_prescription_is_independent_of_the_sale_path(): void
    {
        $pharmacist = $this->pharmacist();
        $patient = Patient::factory()->create();

        $medication = Medication::create([
            'name' => 'Paracétamol',
            'stock_quantity' => 100,
            'unit_price' => 500,
        ]);

        $prescription = $patient->prescriptions()->create([
            'prescribed_by' => $this->pharmacist()->id,
            'requested_at' => now(),
            'stock_status' => 'Disponible',
            'status' => Prescription::STATUS_AWAITING_DISPENSE,
        ]);

        $prescription->items()->create([
            'medication_id' => $medication->id,
            'name' => 'Paracétamol',
            'quantity' => 3,
            'unit_price' => 500,
        ]);

        $total = $prescription->dispense($pharmacist);

        $this->assertSame(1_500, $total);
        $this->assertSame(97, $medication->fresh()->stock_quantity);
        $this->assertSame(Prescription::STATUS_DISPENSED, $prescription->fresh()->status);
    }
}
