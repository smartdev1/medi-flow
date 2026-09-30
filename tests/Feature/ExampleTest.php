<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * La racine de l'application est un point d'entrée protégé : un visiteur
     * non authentifié est renvoyé vers la connexion, et chaque rôle atterrit
     * sur son écran d'accueil.
     */
    public function test_the_application_redirects_visitors_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_a_signed_in_user_lands_on_their_home_screen(): void
    {
        $user = User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/')->assertRedirect(route('caisse.index'));
    }
}
