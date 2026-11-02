<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Emergency session closure by RAF (T-008).
 *
 * A RAF can close any active session without a cash count. The session
 * stays in a "pending audit" state until a closing_balance is set later.
 */
class EmergencyClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_emergency_closed_returns_true_when_closed_by_is_set(): void
    {
        $session = CashierSession::factory()->create([
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
        ]);

        $this->assertTrue($session->isEmergencyClosed());
    }

    public function test_is_emergency_closed_returns_false_when_not_set(): void
    {
        $session = CashierSession::factory()->active()->create();

        $this->assertFalse($session->isEmergencyClosed());
    }

    public function test_can_be_emergency_closed_only_when_active_and_not_already_emergency_closed(): void
    {
        $active = CashierSession::factory()->active()->create();
        $this->assertTrue($active->canBeEmergencyClosed());

        $closed = CashierSession::factory()->closed()->create();
        $this->assertFalse($closed->canBeEmergencyClosed());

        $emergency = CashierSession::factory()->create([
            'closed_at' => now(),
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
        ]);
        $this->assertFalse($emergency->canBeEmergencyClosed());
    }

    public function test_pending_audit_is_true_when_emergency_closed_without_closing_balance(): void
    {
        $session = CashierSession::factory()->create([
            'closed_at' => now(),
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
            'closing_balance' => null,
        ]);

        $this->assertTrue($session->pendingAudit());
    }

    public function test_pending_audit_is_false_when_closing_balance_is_set(): void
    {
        $session = CashierSession::factory()->create([
            'closed_at' => now(),
            'closing_balance' => 5_000,
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
        ]);

        $this->assertFalse($session->pendingAudit());
    }

    public function test_cash_variance_returns_null_when_closing_balance_is_null(): void
    {
        $session = CashierSession::factory()->create([
            'opening_balance' => 5_000,
            'closed_at' => now(),
            'closing_balance' => null,
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
        ]);

        $this->assertNull($session->cashVariance());
    }

    public function test_cash_variance_computes_normally_when_closing_balance_is_set(): void
    {
        $cashier = User::factory()->withRole(Role::Caissier)->create();
        $session = CashierSession::factory()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 5_000,
            'closed_at' => now(),
            'closing_balance' => 7_000,
        ]);

        // No payments, so variance = 5000 - 7000 = -2000
        $this->assertSame(-2000, $session->cashVariance());
    }

    public function test_raf_can_access_emergency_close_form_for_another_cashier_session(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();
        $otherCashier = User::factory()->withRole(Role::Caissier)->create();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $otherCashier->id,
            'opening_balance' => 5_000,
        ]);

        $this->actingAs($raf)
            ->get(route('caisse.sessions.emergency-close-form', $session))
            ->assertOk()
            ->assertSee('Clôture d\'urgence')
            ->assertSee($otherCashier->name);
    }

    public function test_raf_gets_no_session_error_on_normal_close_form(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();

        // RAF has no active session of their own — controller queries by cashier_id
        $this->actingAs($raf)
            ->get(route('caisse.sessions.close'))
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('error', 'Aucune session de caisse en cours à fermer.');
    }

    public function test_raf_gets_no_session_error_on_normal_close_post(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();

        $this->actingAs($raf)
            ->post(route('caisse.sessions.close.store'), [
                'closing_balance' => 5000,
            ])
            ->assertRedirect(route('caisse.index'))
            ->assertSessionHas('error', 'Aucune session de caisse en cours à fermer.');
    }
    public function test_non_raf_gets_403_on_emergency_close_form(): void
    {
        $cashier = User::factory()->withRole(Role::Caissier)->create();
        $otherCashier = User::factory()->withRole(Role::Caissier)->create();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $otherCashier->id,
        ]);

        $this->actingAs($cashier)
            ->get(route('caisse.sessions.emergency-close-form', $session))
            ->assertForbidden();
    }

    public function test_emergency_close_sets_correct_fields_and_keeps_closing_balance_null(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();
        $otherCashier = User::factory()->withRole(Role::Caissier)->create();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $otherCashier->id,
            'opening_balance' => 5_000,
            'closing_balance' => null,
        ]);

        $this->actingAs($raf)
            ->post(route('caisse.sessions.emergency-close', $session), [
                'emergency_closure_reason' => 'Caissier absent',
            ])
            ->assertRedirect(route('caisse.sessions.index'))
            ->assertSessionHas('success');

        $session->refresh();

        $this->assertNotNull($session->closed_at);
        $this->assertSame($raf->id, $session->emergency_closed_by);
        $this->assertNotNull($session->emergency_closed_at);
        $this->assertSame('Caissier absent', $session->emergency_closure_reason);
        $this->assertNull($session->closing_balance, 'Le fond de clôture reste null après une clôture d\'urgence.');
        $this->assertTrue($session->isEmergencyClosed());
        $this->assertTrue($session->pendingAudit());
    }

    public function test_cannot_emergency_close_an_already_closed_session(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();
        $session = CashierSession::factory()->closed()->create([
            'emergency_closed_by' => null,
        ]);

        // Policy blocks RAF from updating non-active sessions
        $this->actingAs($raf)
            ->post(route('caisse.sessions.emergency-close', $session), [
                'emergency_closure_reason' => 'Raison',
            ])
            ->assertForbidden();
    }

    public function test_cannot_emergency_close_an_already_emergency_closed_session(): void
    {
        $raf = User::factory()->withRole(Role::Raf)->create();
        $session = CashierSession::factory()->create([
            'closed_at' => now(),
            'emergency_closed_by' => 1,
            'emergency_closed_at' => now(),
            'emergency_closure_reason' => 'Première urgence',
        ]);

        // Policy blocks RAF from updating non-active sessions
        $this->actingAs($raf)
            ->post(route('caisse.sessions.emergency-close', $session), [
                'emergency_closure_reason' => 'Deuxième urgence',
            ])
            ->assertForbidden();
    }
}
