<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_counts_returns_json_with_expected_keys(): void
    {
        $user = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/api/badges');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'caisse',
                'attente',
                'medecin',
                'laboratoire',
                'admission-requests',
                'hospitalisation',
                'observation',
                'sortie',
            ]);
    }

    public function test_badge_counts_are_integers(): void
    {
        $user = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/api/badges');

        $data = $response->json();
        $this->assertIsInt($data['caisse']);
        $this->assertIsInt($data['attente']);
        $this->assertIsInt($data['medecin']);
        $this->assertIsInt($data['laboratoire']);
        $this->assertIsInt($data['hospitalisation']);
        $this->assertIsInt($data['observation']);
        $this->assertIsInt($data['sortie']);
    }

    public function test_badge_waiting_count_increments_with_new_visit(): void
    {
        $user = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        Visit::factory()->count(2)->waiting()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($user)
            ->get('/api/badges');

        $data = $response->json();
        $this->assertGreaterThanOrEqual(2, $data['attente']);
    }

    public function test_badge_laboratoire_count_reflects_pending_exams(): void
    {
        $user = User::factory()->create([
            'role' => Role::Accueil->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        Exam::factory()->count(3)->awaitingSample()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/api/badges');

        $data = $response->json();
        $this->assertGreaterThanOrEqual(3, $data['laboratoire']);
    }
}
