<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le médecin général est en lecture seule sur les dossiers patients. La règle
 * est portée par PatientPolicy, pas par un drapeau sur le modèle : c'est
 * là qu'elle est vérifiée.
 */
class DoctorGeneralReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_docteur_general_cannot_update_a_patient(): void
    {
        $doctor = User::factory()->create(['role' => Role::DocteurGeneral->value]);
        $patient = Patient::factory()->create();

        $this->assertFalse($doctor->can('update', $patient));
    }

    public function test_a_docteur_general_can_read_a_patient(): void
    {
        $doctor = User::factory()->create(['role' => Role::DocteurGeneral->value]);
        $patient = Patient::factory()->create();

        $this->assertTrue($doctor->can('view', $patient));
    }

    public function test_the_receptionist_can_still_update_a_patient(): void
    {
        $receptionist = User::factory()->create(['role' => Role::Accueil->value]);
        $patient = Patient::factory()->create();

        $this->assertTrue($receptionist->can('update', $patient));
    }
}
