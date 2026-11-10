<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvoiceNotFoundException;
use PHPUnit\Framework\TestCase;

final class InvoiceNotFoundExceptionTest extends TestCase
{
    public function test_should_instantiate_with_custom_message(): void
    {
        $exception = new InvoiceNotFoundException('Invoice not found.');

        $this->assertSame('Invoice not found.', $exception->getMessage());
    }

    public function test_should_instantiate_with_named_constructors(): void
    {
        $byId = InvoiceNotFoundException::forId('mongo-id-123');
        $this->assertSame("Invoice with ID 'mongo-id-123' not found.", $byId->getMessage());

        $byWorkOrder = InvoiceNotFoundException::forWorkOrder(45);
        $this->assertSame('Invoice for Work Order #45 not found.', $byWorkOrder->getMessage());

        $byPreference = InvoiceNotFoundException::forPreferenceId('pref-xyz-890');
        $this->assertSame("Invoice with preference ID 'pref-xyz-890' not found.", $byPreference->getMessage());
    }
}