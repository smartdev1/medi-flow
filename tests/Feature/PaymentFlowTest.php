<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SessionTransfer;
use App\Models\User;
use App\Models\Versement;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        return User::factory()->create([
            'role' => Role::Caissier->value,
            'email_verified_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => null,
            'service' => 'Consultation',
            'base_amount' => 10_000,
            'taux' => 20,
            'amount' => 8_000,
            'method' => 'Espèces',
            'status' => Payment::STATUS_PAID,
        ], $overrides);
    }

    public function test_caissier_can_create_payment_with_an_open_session(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();
        $session = CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 20_000,
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), $this->payload(['patient_id' => $patient->id]));

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'patient_id' => $patient->id,
            'amount' => 8_000,
            'status' => Payment::STATUS_PAID,
            'cashier_id' => $cashier->id,
            'cashier_session_id' => $session->id,
        ]);
    }

    public function test_payment_is_refused_without_an_open_session(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), $this->payload(['patient_id' => $patient->id]));

        $response->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_amount_must_match_the_ticket_moderateur_calculation(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();
        CashierSession::factory()->active()->create(['cashier_id' => $cashier->id]);

        $response = $this->actingAs($cashier)
            ->post(route('payments.store'), $this->payload([
                'patient_id' => $patient->id,
                'base_amount' => 10_000,
                'taux' => 20,
                'amount' => 10_000, // devrait être 8 000
            ]));

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_caissier_can_close_a_cashier_session(): void
    {
        $cashier = $this->cashier();
        CashierSession::factory()->active()->create([
            'cashier_id' => $cashier->id,
            'opening_balance' => 10_000,
        ]);

        $response = $this->actingAs($cashier)
            ->post(route('caisse.sessions.close.store'), [
                'closing_balance' => 10_000,
            ]);

        $response->assertRedirect(route('caisse.index'));
        $response->assertSessionHas('success');

        $this->assertNotNull(
            CashierSession::where('cashier_id', $cashier->id)->first()->closed_at
        );
    }

    public function test_cashier_cannot_modify_other_cashier_payment(): void
    {
        $cashier1 = $this->cashier();
        $cashier2 = $this->cashier();

        $payment = Payment::factory()->paid()->create(['cashier_id' => $cashier1->id]);

        $this->actingAs($cashier2)
            ->post(route('payments.cancel', $payment))
            ->assertForbidden();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_cancelling_a_payment_reopens_the_invoice(): void
    {
        $cashier = $this->cashier();
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->inConsultation()->create(['patient_id' => $patient->id]);

        $invoice = Invoice::factory()->paid()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'patient_amount' => 10_000,
            'paid_amount' => 10_000,
        ]);

        $payment = Payment::factory()->paid()->create([
            'invoice_id' => $invoice->id,
            'patient_id' => $patient->id,
            'amount' => 10_000,
            'cashier_id' => $cashier->id,
        ]);

        $this->actingAs($cashier)->post(route('payments.cancel', $payment));

        $this->assertSame(Payment::STATUS_CANCELLED, $payment->fresh()->status);
        $this->assertSame(0, (int) $invoice->fresh()->paid_amount);
        $this->assertFalse($invoice->fresh()->isPaid());
    }
}
