<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurgeryConsentTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Pre-anesthetic notes
    // ------------------------------------------------------------------

    public function test_anesthetist_can_fill_pre_anesthetic_notes(): void
    {
        $anesthetist = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'anesthetist_id' => $anesthetist->id,
        ]);

        $response = $this->actingAs($anesthetist)
            ->patch(route('surgery.pre-anesthetic', $surgery), [
                'pre_anesthetic_notes' => 'Patient is fit for general anesthesia. No allergies noted.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(
            'Patient is fit for general anesthesia. No allergies noted.',
            $surgery->fresh()->pre_anesthetic_notes,
        );
    }

    public function test_any_medecin_can_fill_if_no_anesthetist_assigned(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'anesthetist_id' => null,
        ]);

        $response = $this->actingAs($medecin)
            ->patch(route('surgery.pre-anesthetic', $surgery), [
                'pre_anesthetic_notes' => 'Pre-anesthetic evaluation completed without issues.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(
            'Pre-anesthetic evaluation completed without issues.',
            $surgery->fresh()->pre_anesthetic_notes,
        );
    }

    public function test_non_assigned_medecin_cannot_fill_if_anesthetist_exists(): void
    {
        $assignedAnesthetist = User::factory()->withRole(Role::Medecin)->create();
        $otherMedecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'anesthetist_id' => $assignedAnesthetist->id,
        ]);

        $response = $this->actingAs($otherMedecin)
            ->patch(route('surgery.pre-anesthetic', $surgery), [
                'pre_anesthetic_notes' => 'Trying to write notes as non-assigned medecin.',
            ]);

        $response->assertStatus(403);

        $this->assertNull($surgery->fresh()->pre_anesthetic_notes);
    }

    // ------------------------------------------------------------------
    // Consent signing
    // ------------------------------------------------------------------

    public function test_consent_signing_transitions_status(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'pre_anesthetic_notes' => 'Patient cleared for surgery.',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)
            ->post(route('surgery.consent', $surgery), [
                'consent_signed_by' => 'Jean Dupont',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();

        $this->assertSame(Surgery::STATUS_CONSENT_SIGNED, $surgery->status);
        $this->assertNotNull($surgery->consent_signed_at);
        $this->assertSame('Jean Dupont', $surgery->consent_signed_by);
    }

    public function test_consent_blocked_without_pre_anesthetic_notes(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'pre_anesthetic_notes' => null,
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)
            ->post(route('surgery.consent', $surgery), [
                'consent_signed_by' => 'Jean Dupont',
            ]);

        $response->assertStatus(422);

        $surgery->refresh();

        $this->assertSame(Surgery::STATUS_SCHEDULED, $surgery->status);
        $this->assertNull($surgery->consent_signed_at);
    }

    public function test_consent_signed_by_is_recorded(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'pre_anesthetic_notes' => 'All checks passed. Fit for surgery.',
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $this->actingAs($medecin)
            ->post(route('surgery.consent', $surgery), [
                'consent_signed_by' => 'Marie Konan (tutrice)',
            ]);

        $this->assertDatabaseHas('surgeries', [
            'id' => $surgery->id,
            'consent_signed_by' => 'Marie Konan (tutrice)',
        ]);
    }
}
