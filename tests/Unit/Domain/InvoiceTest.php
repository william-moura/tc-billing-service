<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entities\Invoice;
use App\Domain\Exceptions\InvalidPaymentOperationException;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    public function test_should_create_pending_invoice(): void
    {
        $invoice = Invoice::create(workOrderId: 101, customerId: 1, amount: new Money(250.0));

        $this->assertSame(PaymentStatus::PENDING, $invoice->getStatus());
        $this->assertSame(250.0, $invoice->getAmount()->getAmount());
    }

    public function test_should_approve_payment(): void
    {
        $invoice = Invoice::create(101, 1, new Money(100.0));
        $invoice->markAsApproved('mp-trans-1234');

        $this->assertSame(PaymentStatus::APPROVED, $invoice->getStatus());
        $this->assertCount(1, $invoice->getAttempts());
        $this->assertSame('mp-trans-1234', $invoice->getAttempts()[0]->getTransactionId());
    }

    public function test_should_throw_exception_when_rejecting_already_approved_invoice(): void
    {
        $this->expectException(InvalidPaymentOperationException::class);

        $invoice = Invoice::create(101, 1, new Money(100.0));
        $invoice->markAsApproved('mp-trans-1234');
        $invoice->markAsRejected('mp-trans-5678', 'Late reject');
    }
}