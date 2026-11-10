<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

final class InvoiceNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Invoice with ID '{$id}' not found.");
    }

    public static function forWorkOrder(int $workOrderId): self
    {
        return new self("Invoice for Work Order #{$workOrderId} not found.");
    }

    public static function forPreferenceId(string $preferenceId): self
    {
        return new self("Invoice with preference ID '{$preferenceId}' not found.");
    }
}