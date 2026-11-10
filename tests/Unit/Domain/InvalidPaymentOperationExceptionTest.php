<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvalidPaymentOperationException;
use PHPUnit\Framework\TestCase;

final class InvalidPaymentOperationExceptionTest extends TestCase
{
    public function test_should_instantiate_with_custom_message(): void
    {
        $exception = new InvalidPaymentOperationException('Custom domain error');

        $this->assertSame('Custom domain error', $exception->getMessage());
    }

    public function test_should_create_named_constructors(): void
    {
        $alreadyApproved = InvalidPaymentOperationException::alreadyApproved();
        $this->assertSame('Cannot reject or modify an already approved invoice.', $alreadyApproved->getMessage());

        $cannotRefund = InvalidPaymentOperationException::cannotRefundNonApproved('PENDING');
        $this->assertSame('Cannot refund invoice with status PENDING. Only APPROVED invoices can be refunded.', $cannotRefund->getMessage());

        $missingTx = InvalidPaymentOperationException::missingApprovedTransaction(42);
        $this->assertSame('No approved transaction ID found to refund for Work Order #42.', $missingTx->getMessage());
    }
}