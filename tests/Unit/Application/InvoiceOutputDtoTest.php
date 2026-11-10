<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTOs\InvoiceOutputDto;
use App\Domain\Entities\Invoice;
use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class InvoiceOutputDtoTest extends TestCase
{
    public function test_should_transform_invoice_entity_into_output_dto(): void
    {
        $invoice = Invoice::create(workOrderId: 55, customerId: 10, amount: new Money(350.00));
        $invoice->attachGatewayPreference('pref-abc', 'https://mercadopago.com/checkout/123');
        $invoice->markAsApproved('trans-999');

        $dto = InvoiceOutputDto::fromDomain($invoice);

        $this->assertSame(55, $dto->workOrderId);
        $this->assertSame(10, $dto->customerId);
        $this->assertSame(350.00, $dto->amount);
        $this->assertSame('BRL', $dto->currency);
        $this->assertSame('APPROVED', $dto->status);
        $this->assertSame('pref-abc', $dto->preferenceId);
        $this->assertSame('https://mercadopago.com/checkout/123', $dto->paymentUrl);
        $this->assertCount(1, $dto->attempts);
        $this->assertSame('trans-999', $dto->attempts[0]['transaction_id']);
    }
}