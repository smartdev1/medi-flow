<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les écrans de vacation (ouverture, fermeture, liste, détail) sont rendus par
 * une action du caissier. Aucun ne l'était par les tests, alors qu'ils
 * dépendent tous de l'identité du cadre commun : il suffisait qu'une vue
 * réclame $userName pour que l'écran soit inaccessible.
 */
class CashierSessionScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cashier_can_open_a_session(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.open'))
            ->assertOk()
            ->assertSee($cashier->name);

        $this->actingAs($cashier)
            ->post(route('caisse.sessions.open.store'), ['opening_balance' => 5_000])
            ->assertRedirect(route('caisse.index'));

        $this->assertDatabaseHas('cashier_sessions', [
            'cashier_id' => $cashier->id,
            'opening_balance' => 5_000,
        ]);
    }

    public function test_the_cashier_can_close_a_session(): void
    {
        $cashier = $this->cashier();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 5_000,
        ]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'))
            ->assertOk()
            ->assertSee($cashier->name);

        $this->actingAs($cashier)
            ->post(route('caisse.sessions.close.store'), ['closing_balance' => 5_000])
            ->assertRedirect(route('caisse.index'));

        // Comparer `closed_at` à la seconde fait échouer le test dès que la
        // requête franchit une seconde entre la lecture de l'heure et
        // l'enregistrement : seule la fermeture compte ici.
        $session->refresh();

        $this->assertNotNull($session->closed_at, 'La vacation doit être clôturée.');
        $this->assertTrue($session->isClosed());
        $this->assertSame(5_000, $session->closing_balance);
    }

    public function test_the_session_list_renders(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->closed()->create(['cashier_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.index'))
            ->assertOk()
            ->assertSee($cashier->name);
    }

    public function test_the_session_detail_renders(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 5_000,
        ]);

        $session->payments()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'cashier_id' => $cashier->id,
            'service' => 'Consultation',
            'amount' => 10_000,
            'method' => 'Espèces',
            'status' => \App\Models\Payment::STATUS_PAID,
        ]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.show', $session))
            ->assertOk()
            ->assertSee($cashier->name)
            ->assertSee($patient->full_name);
    }

    /**
     * Le doctor général supervise toutes les vacations, y compris celles des
     * autres caissiers.
     */
    public function test_the_docteur_general_sees_every_session(): void
    {
        $doctor = User::factory()->withRole(Role::DocteurGeneral)->create();
        $other = User::factory()->withRole(Role::Caissier)->create();
        CashierSession::factory()->closed()->create(['cashier_id' => $other->id]);

        $this->actingAs($doctor)
            ->get(route('caisse.sessions.index'))
            ->assertOk()
            ->assertSee($other->name);
    }

    public function test_a_cashier_cannot_open_a_second_session(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.open'))
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('error');
    }

    public function test_closing_without_a_session_is_refused(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'))
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('error');
    }

    private function cashier(): User
    {
        return User::factory()->withRole(Role::Caissier)->create();
    }
}
