<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DischargeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sortie_controller_allows_discharge_when_invoice_paid(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        Invoice::factory()->paid()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }

    public function test_sortie_controller_blocks_discharge_when_large_unpaid_balance(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        Invoice::factory()->pending()->withAmounts(50000)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('error');

        $visit->refresh();
        $this->assertNull($visit->discharged_at);
    }

    public function test_sortie_controller_blocks_discharge_even_for_a_tiny_remaining_balance(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        // 500 FCFA restant : sous l'ancien seuil de tolérance de 1000, la
        // sortie passait. Aucun reste n'est désormais toléré.
        Invoice::factory()->pending()->withAmounts(500)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('warning');

        $visit->refresh();
        $this->assertNull($visit->discharged_at);
    }

    public function test_sortie_controller_blocks_discharge_while_a_partial_payment_is_owed(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        Invoice::factory()->pending()->withAmounts(50000)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 20000,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertSessionHas('error');

        $visit->refresh();
        $this->assertNull($visit->discharged_at);
    }

    public function test_sortie_controller_allows_discharge_when_insurance_covers_the_whole_invoice(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        // La facture existe mais ne coûte rien au patient : la règle porte sur
        // le solde restant, pas sur la simple présence d'une facture.
        Invoice::factory()->pending()->withAmounts(50000, 50000)->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }

    public function test_sortie_controller_allows_discharge_when_no_invoice_was_raised(): void
    {
        $user = User::factory()->create([
            'role' => Role::DocteurGeneral->value,
            'email_verified_at' => now(),
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'status' => Visit::STATUS_CONSULTATION_CLOSED,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sortie.close', $visit));

        $response->assertRedirect(route('sortie.index', ['patient' => $patient->id]));
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertNotNull($visit->discharged_at);
    }
}
