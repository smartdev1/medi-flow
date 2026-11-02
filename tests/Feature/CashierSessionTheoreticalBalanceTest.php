<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Act;
use App\Models\CashierSession;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AC4: Tests for the theoretical closing balance display at session close.
 */
class CashierSessionTheoreticalBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        return User::factory()->withRole(Role::Caissier)->create([
            'email_verified_at' => now(),
        ]);
    }

    private function createActiveSession(int $openingBalance, User $cashier): CashierSession
    {
        return CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => $openingBalance,
        ]);
    }

    // --- AC1: Model ---

    public function test_theoretical_balance_returns_opening_balance_plus_settled_amount(): void
    {
        $cashier = $this->cashier();
        $session = $this->createActiveSession(5_000, $cashier);

        // Add a PAID payment
        $session->payments()->create([
            'patient_id' => Patient::factory()->create()->id,
            'amount' => 10_000,
            'status' => Payment::STATUS_PAID,
            'service' => 'Consultation',
            'method' => 'Espèces',
            'cashier_id' => $cashier->id,
        ]);

        $this->assertSame(15_000, $session->theoreticalBalance());
    }

    public function test_theoretical_balance_with_no_payments_returns_opening_balance(): void
    {
        $cashier = $this->cashier();
        $session = $this->createActiveSession(3_000, $cashier);

        $this->assertSame(3_000, $session->theoreticalBalance());
    }

    // --- AC2: Controller ---

    public function test_close_form_passes_theoretical_balance_to_view(): void
    {
        $cashier = $this->cashier();
        $session = $this->createActiveSession(7_000, $cashier);

        $response = $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'));

        $response->assertOk();
        $response->assertViewHas('theoreticalBalance', 7_000);
    }

    public function test_close_form_passes_settled_payments_to_view(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $act = Act::factory()->create(['label' => 'Radio']);

        $session = $this->createActiveSession(2_000, $cashier);
        $session->payments()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'act_id' => $act->id,
            'amount' => 12_000,
            'status' => Payment::STATUS_PAID,
            'service' => 'Radio',
            'method' => 'Espèces',
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'));

        $response->assertOk();
        $response->assertViewHas('settledPayments');
        $this->assertCount(1, $response->viewData('settledPayments'));
    }

    // --- AC3: View ---

    public function test_view_displays_theoretical_balance_value(): void
    {
        $cashier = $this->cashier();
        $session = $this->createActiveSession(4_000, $cashier);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'))
            ->assertOk()
            ->assertSee('4 000')
            ->assertSee('Fond théorique');
    }

    public function test_view_displays_payment_list_with_correct_data(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);
        $act = Act::factory()->create(['label' => 'Consultation']);

        $session = $this->createActiveSession(5_000, $cashier);
        $session->payments()->create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'act_id' => $act->id,
            'amount' => 15_000,
            'status' => Payment::STATUS_PAID,
            'service' => 'Consultation',
            'method' => 'Espèces',
            'cashier_id' => $cashier->id,
        ]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.close'))
            ->assertOk()
            ->assertSee('Jean Dupont')
            ->assertSee('Consultation')
            ->assertSee('15 000')
            ->assertSee('Espèces');
    }
}
