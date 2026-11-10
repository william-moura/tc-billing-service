<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Services\MessagePublisherInterface;
use App\Application\Services\PaymentGatewayInterface;
use App\Application\UseCases\RefundPaymentUseCase;
use App\Domain\Entities\Invoice;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Exceptions\InvalidPaymentOperationException;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\PaymentStatus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RefundPaymentUseCaseTest extends TestCase
{
    public function test_should_refund_approved_invoice_successfully(): void
    {
        $invoice = Invoice::create(workOrderId: 10, customerId: 1, amount: new Money(150.00));
        $invoice->markAsApproved('mp-tx-12345');

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByWorkOrderId')
            ->with(10)
            ->willReturn($invoice);

        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(fn(Invoice $inv) => $inv);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->expects($this->once())
            ->method('refund')
            ->with('mp-tx-12345')
            ->willReturn(true);

        $publisher = $this->createMock(MessagePublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with(
                'queue.payment.refunded',
                $this->callback(function (array $payload) {
                    return $payload['event'] === 'payment.refunded'
                        && $payload['work_order_id'] === 10
                        && $payload['transaction_id'] === 'mp-tx-12345';
                })
            );

        $useCase = new RefundPaymentUseCase($repository, $gateway, $publisher);
        $dto = $useCase->execute(10, 'Production service aborted');

        $this->assertSame(PaymentStatus::REFUNDED->value, $dto->status);
    }

    public function test_should_throw_exception_when_invoice_not_found(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->method('findByWorkOrderId')->willReturn(null);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $publisher = $this->createMock(MessagePublisherInterface::class);

        $useCase = new RefundPaymentUseCase($repository, $gateway, $publisher);
        $useCase->execute(999, 'Reason');
    }

    public function test_should_throw_exception_when_refunding_non_approved_invoice(): void
    {
        $this->expectException(InvalidPaymentOperationException::class);

        $invoice = Invoice::create(workOrderId: 20, customerId: 2, amount: new Money(80.00));
        // Status ainda está PENDING

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->method('findByWorkOrderId')->willReturn($invoice);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $publisher = $this->createMock(MessagePublisherInterface::class);

        $useCase = new RefundPaymentUseCase($repository, $gateway, $publisher);
        $useCase->execute(20, 'Reason');
    }

    public function test_should_throw_exception_when_gateway_fails_to_refund(): void
    {
        $this->expectException(RuntimeException::class);

        $invoice = Invoice::create(workOrderId: 30, customerId: 3, amount: new Money(200.00));
        $invoice->markAsApproved('mp-tx-999');

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->method('findByWorkOrderId')->willReturn($invoice);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('refund')->willReturn(false);

        $publisher = $this->createMock(MessagePublisherInterface::class);

        $useCase = new RefundPaymentUseCase($repository, $gateway, $publisher);
        $useCase->execute(30, 'Reason');
    }
}