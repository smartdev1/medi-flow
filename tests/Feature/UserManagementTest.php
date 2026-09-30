<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_a_role_from_users_page(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'role' => Role::Caissier->value,
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'jean@example.com', 'role' => Role::Caissier->value]);
    }
}
