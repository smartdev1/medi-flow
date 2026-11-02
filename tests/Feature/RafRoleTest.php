<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RafRoleTest extends TestCase
{
    use RefreshDatabase;

    private function raf(): User
    {
        return User::factory()->create([
            'role' => Role::Raf->value,
            'email_verified_at' => now(),
        ]);
    }

    private function caissier(): User
    {
        return User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);
    }

    public function test_raf_can_view_all_sessions_in_index(): void
    {
        $rafUser = $this->raf();
        $cashierA = $this->caissier();
        $cashierB = $this->caissier();

        CashierSession::factory()->create(['cashier_id' => $cashierA->id]);
        CashierSession::factory()->create(['cashier_id' => $cashierB->id]);

        $this->actingAs($rafUser)
            ->get(route('caisse.sessions.index'))
            ->assertOk()
            ->assertSee($cashierA->name)
            ->assertSee($cashierB->name);
    }

    public function test_raf_can_view_a_single_session_detail_from_another_cashier(): void
    {
        $rafUser = $this->raf();
        $otherCashier = $this->caissier();

        $session = CashierSession::factory()->create(['cashier_id' => $otherCashier->id]);

        $this->actingAs($rafUser)
            ->get(route('caisse.sessions.show', $session))
            ->assertOk()
            ->assertSee($otherCashier->name);
    }

    public function test_raf_cannot_create_sessions(): void
    {
        $rafUser = $this->raf();

        $this->actingAs($rafUser)
            ->get(route('caisse.sessions.open'))
            ->assertForbidden();

        $this->actingAs($rafUser)
            ->post(route('caisse.sessions.open.store'), [
                'opening_balance' => 5000,
            ])->assertForbidden();
    }

    public function test_caissier_only_sees_own_sessions(): void
    {
        $cashierA = $this->caissier();
        $cashierB = $this->caissier();

        CashierSession::factory()->create(['cashier_id' => $cashierA->id]);
        CashierSession::factory()->create(['cashier_id' => $cashierB->id]);

        $this->actingAs($cashierA)
            ->get(route('caisse.sessions.index'))
            ->assertOk()
            ->assertSee($cashierA->name)
            ->assertDontSee($cashierB->name);
    }
}
