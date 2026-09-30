<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La supervision est l'écran d'arrivée du docteur général : la route existe,
 * mais si l'entrée disparaît du menu il atterrit dessus sans plus jamais pouvoir
 * y revenir. Aucun test ne rendait la navigation, la régression est donc
 * passée inaperçue.
 */
class SupervisionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_docteur_general_sees_the_supervision_link(): void
    {
        $doctor = User::factory()->withRole(Role::DocteurGeneral)->create();

        $this->actingAs($doctor)
            ->get(route('docteurgeneral.index'))
            ->assertOk()
            ->assertSee(route('docteurgeneral.index'), false);
    }

    public function test_the_cashier_does_not_see_the_supervision_link(): void
    {
        $cashier = User::factory()->withRole(Role::Caissier)->create();

        $this->actingAs($cashier)
            ->get(route('caisse.index'))
            ->assertOk()
            ->assertDontSee(route('docteurgeneral.index'), false);
    }

    public function test_a_specialist_does_not_see_the_supervision_link(): void
    {
        $doctor = User::factory()->withRole(Role::Medecin)->create();

        $this->actingAs($doctor)
            ->get(route('medecin.index'))
            ->assertOk()
            ->assertDontSee(route('docteurgeneral.index'), false);
    }

    public function test_the_admin_keeps_the_supervision_link(): void
    {
        $admin = User::factory()->withRole(Role::Admin)->create();

        $this->actingAs($admin)
            ->get(route('accueil.index'))
            ->assertOk()
            ->assertSee(route('docteurgeneral.index'), false);
    }

    public function test_a_non_supervisor_is_refused_on_the_supervision_page(): void
    {
        $cashier = User::factory()->withRole(Role::Caissier)->create();

        $this->actingAs($cashier)
            ->get(route('docteurgeneral.index'))
            ->assertForbidden();
    }
}
