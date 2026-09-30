<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Invoice;
use PHPUnit\Framework\TestCase;

class PaymentModelTest extends TestCase
{
    public function test_has_all_expected_status_constants(): void
    {
        $this->assertSame('Payé', Payment::STATUS_PAID);
        $this->assertSame('Partiellement payé', Payment::STATUS_PARTIAL);
        $this->assertSame('En attente', Payment::STATUS_PENDING);
    }

    public function test_status_tone_accessor_returns_success_for_paid(): void
    {
        $payment = new Payment(['status' => Payment::STATUS_PAID]);
        $this->assertSame('success', $payment->status_tone);
    }

    public function test_status_tone_accessor_returns_warning_for_partial(): void
    {
        $payment = new Payment(['status' => Payment::STATUS_PARTIAL]);
        $this->assertSame('warning', $payment->status_tone);
    }

    public function test_status_tone_accessor_returns_neutral_for_pending(): void
    {
        $payment = new Payment(['status' => Payment::STATUS_PENDING]);
        $this->assertSame('neutral', $payment->status_tone);
    }

    public function test_formatted_amount_attribute(): void
    {
        $payment = new Payment(['amount' => 15000]);
        $this->assertSame('15 000 FCFA', $payment->formatted_amount);
    }

    public function test_has_cancellation_and_refund_statuses(): void
    {
        $this->assertSame('Annulé', Payment::STATUS_CANCELLED);
        $this->assertSame('Remboursé', Payment::STATUS_REFUNDED);
    }

    public function test_scope_settled_exists_instead_of_day_close_scopes(): void
    {
        $reflection = new \ReflectionClass(Payment::class);

        // La clôture de journée est remplacée par CashierSession : les scopes
        // open/closed sur closed_at n'ont plus de sens.
        $this->assertTrue($reflection->hasMethod('scopeSettled'));
        $this->assertFalse($reflection->hasMethod('scopeOpen'));
        $this->assertFalse($reflection->hasMethod('scopeClosed'));
    }

    public function test_voided_payments_are_not_settled(): void
    {
        $cancelled = new Payment(['status' => Payment::STATUS_CANCELLED]);
        $refunded = new Payment(['status' => Payment::STATUS_REFUNDED]);
        $paid = new Payment(['status' => Payment::STATUS_PAID]);

        $this->assertTrue($cancelled->isVoided());
        $this->assertTrue($refunded->isVoided());
        $this->assertFalse($paid->isVoided());

        $this->assertTrue($paid->isSettled());
        $this->assertFalse($cancelled->isSettled());
    }

    public function test_fillable_keeps_invoice_link_for_reconciliation(): void
    {
        $fillable = (new Payment)->getFillable();

        $this->assertContains('invoice_id', $fillable);
        $this->assertContains('cashier_session_id', $fillable);
        $this->assertNotContains('closed_at', $fillable);
    }
}
