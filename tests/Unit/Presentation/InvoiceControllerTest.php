<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Domain\Entities\Invoice;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\PaymentStatus;
use App\Presentation\Http\Controllers\InvoiceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

final class InvoiceControllerTest extends TestCase
{
    public function test_show_should_return_invoice_json_response_when_found(): void
    {
        $invoice = new Invoice(
            id: '64f1a2b3c4d5e6f7a8b9c0d1',
            workOrderId: 10,
            customerId: 2,
            amount: new Money(180.50),
            status: PaymentStatus::PENDING,
            preferenceId: 'pref-123',
            paymentUrl: 'https://mercadopago.com/checkout/pay'
        );

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with('64f1a2b3c4d5e6f7a8b9c0d1')
            ->willReturn($invoice);

        $controller = new InvoiceController($repository);
        $response = $controller->show('64f1a2b3c4d5e6f7a8b9c0d1');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = $response->getData(true)['data'];
        $this->assertSame('64f1a2b3c4d5e6f7a8b9c0d1', $data['id']);
        $this->assertSame(10, $data['work_order_id']);
        $this->assertSame(180.50, $data['amount']);
        $this->assertSame('PENDING', $data['status']);
    }

    public function test_show_should_throw_exception_when_invoice_not_found(): void
    {
        $this->expectException(InvoiceNotFoundException::class);

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with('non-existent-id')
            ->willReturn(null);

        $controller = new InvoiceController($repository);
        $controller->show('non-existent-id');
    }

    public function test_find_by_work_order_should_return_invoice_when_found(): void
    {
        $invoice = new Invoice(
            id: '64f1a2b3c4d5e6f7a8b9c0d2',
            workOrderId: 45,
            customerId: 8,
            amount: new Money(320.00),
            status: PaymentStatus::APPROVED
        );

        $repository = $this->createMock(InvoiceRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findByWorkOrderId')
            ->with(45)
            ->willReturn($invoice);

        $controller = new InvoiceController($repository);
        $response = $controller->findByWorkOrder(45);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = $response->getData(true)['data'];
        $this->assertSame(45, $data['work_order_id']);
        $this->assertSame('APPROVED', $data['status']);
    }
}