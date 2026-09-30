<?php

namespace Tests\Unit;

use App\Models\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceModelTest extends TestCase
{
    public function test_has_all_expected_status_constants(): void
    {
        $this->assertSame('pending', Invoice::STATUS_PENDING);
        $this->assertSame('awaiting_payment', Invoice::STATUS_AWAITING_PAYMENT);
        $this->assertSame('paid', Invoice::STATUS_PAID);
    }

    public function test_is_paid_returns_true_when_paid(): void
    {
        $invoice = new Invoice(['status' => Invoice::STATUS_PAID]);
        $this->assertTrue($invoice->isPaid());
    }

    public function test_is_paid_returns_false_when_pending(): void
    {
        $invoice = new Invoice(['status' => Invoice::STATUS_PENDING]);
        $this->assertFalse($invoice->isPaid());
    }

    public function test_is_pending_returns_true_when_pending(): void
    {
        $invoice = new Invoice(['status' => Invoice::STATUS_PENDING]);
        $this->assertTrue($invoice->isPending());
    }

    public function test_is_awaiting_payment_returns_true_when_awaiting(): void
    {
        $invoice = new Invoice(['status' => Invoice::STATUS_AWAITING_PAYMENT]);
        $this->assertTrue($invoice->isAwaitingPayment());
    }

    public function test_formatted_subtotal_attribute(): void
    {
        $invoice = new Invoice(['subtotal' => 25000]);
        $this->assertSame('25 000 FCFA', $invoice->formatted_subtotal);
    }

    public function test_formatted_patient_amount_attribute(): void
    {
        $invoice = new Invoice(['patient_amount' => 8000]);
        $this->assertSame('8 000 FCFA', $invoice->formatted_patient_amount);
    }

    public function test_recalculate_method_exists(): void
    {
        $reflection = new \ReflectionClass(Invoice::class);
        $this->assertTrue($reflection->hasMethod('recalculate'));
    }

    public function test_mark_as_paid_method_exists(): void
    {
        $reflection = new \ReflectionClass(Invoice::class);
        $this->assertTrue($reflection->hasMethod('markAsPaid'));
    }
}
