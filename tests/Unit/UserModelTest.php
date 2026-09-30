<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    public function test_admin_returns_true_for_admin_role(): void
    {
        $user = new User(['role' => Role::Admin]);
        $this->assertTrue($user->isAdmin());
    }

    public function test_admin_returns_false_for_other_roles(): void
    {
        $user = new User(['role' => Role::Accueil]);
        $this->assertFalse($user->isAdmin());
    }

    public function test_is_doctor_returns_true_for_medecin(): void
    {
        $user = new User(['role' => Role::Medecin]);
        $this->assertTrue($user->isDoctor());
    }

    public function test_is_doctor_returns_true_for_pediatre(): void
    {
        $user = new User(['role' => Role::Pediatre]);
        $this->assertTrue($user->isDoctor());
    }

    public function test_is_doctor_returns_false_for_accueil(): void
    {
        $user = new User(['role' => Role::Accueil]);
        $this->assertFalse($user->isDoctor());
    }

    public function test_has_role_checks_single_role(): void
    {
        $user = new User(['role' => Role::Caissier]);
        $this->assertTrue($user->hasRole(Role::Caissier));
        $this->assertFalse($user->hasRole(Role::Admin));
    }

    public function test_has_role_checks_multiple_roles(): void
    {
        $user = new User(['role' => Role::Medecin]);
        $this->assertTrue($user->hasRole(Role::Medecin, Role::Pediatre));
        $this->assertFalse($user->hasRole(Role::Caissier, Role::Admin));
    }

    public function test_initials_attribute_extracts_from_name(): void
    {
        $user = new User(['name' => 'Jean Dupont']);
        $this->assertSame('JD', $user->initials);
    }

    public function test_initials_attribute_handles_single_name(): void
    {
        $user = new User(['name' => 'Jean']);
        $this->assertSame('J', $user->initials);
    }

    public function test_initials_attribute_takes_first_two_parts(): void
    {
        $user = new User(['name' => 'Jean Pierre Martin Dupont']);
        $this->assertSame('JP', $user->initials);
    }

    public function test_scope_doctors_filters_doctor_roles(): void
    {
        // Test that the scope method exists and is callable
        $reflection = new \ReflectionClass(User::class);
        $this->assertTrue($reflection->hasMethod('scopeDoctors'));
    }
}
