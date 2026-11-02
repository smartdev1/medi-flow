<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RafDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function raf(): User
    {
        return User::factory()->withRole(Role::Raf)->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_raf_can_access_dashboard(): void
    {
        $raf = $this->raf();

        $this->actingAs($raf)
            ->get(route('caisse.raf.dashboard'))
            ->assertOk()
            ->assertSee('Supervision financière');
    }

    public function test_non_raf_gets_403_on_dashboard(): void
    {
        $cashier = User::factory()->withRole(Role::Caissier)->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('caisse.raf.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_passes_financial_data(): void
    {
        $raf = $this->raf();

        $this->actingAs($raf)
            ->get(route('caisse.raf.dashboard'))
            ->assertOk()
            ->assertViewHas('finance')
            ->assertViewHas('monthlyRevenue')
            ->assertViewHas('periodLabel');
    }

    public function test_dashboard_respects_period_parameter(): void
    {
        $raf = $this->raf();

        $this->actingAs($raf)
            ->get(route('caisse.raf.dashboard', ['period' => '7d']))
            ->assertOk()
            ->assertViewHas('period', '7d')
            ->assertViewHas('periodLabel', '7 derniers jours');
    }

    public function test_dashboard_default_period_is_month(): void
    {
        $raf = $this->raf();

        $this->actingAs($raf)
            ->get(route('caisse.raf.dashboard'))
            ->assertOk()
            ->assertViewHas('period', 'month')
            ->assertViewHas('periodLabel', 'Ce mois');
    }
}
