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

    public function test_consent_recorded_when_starting_surgery(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)
            ->post(route('surgery.start', $surgery), [
                'consent' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();

        $this->assertSame(Surgery::STATUS_IN_PROGRESS, $surgery->status);
        $this->assertNotNull($surgery->consent_signed_at);
        $this->assertNotNull($surgery->started_at);
    }

    public function test_start_requires_consent_checkbox(): void
    {
        $medecin = User::factory()->withRole(Role::Medecin)->create();

        $surgery = Surgery::factory()->create([
            'status' => Surgery::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($medecin)
            ->post(route('surgery.start', $surgery), []);

        $response->assertSessionHasErrors('consent');

        $surgery->refresh();
        $this->assertSame(Surgery::STATUS_SCHEDULED, $surgery->status);
    }
}
