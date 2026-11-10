<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

final class InvalidPaymentOperationException extends DomainException
{
    public static function alreadyApproved(): self
    {
        return new self('Cannot reject or modify an already approved invoice.');
    }

    public static function cannotRefundNonApproved(string $currentStatus): self
    {
        return new self("Cannot refund invoice with status {$currentStatus}. Only APPROVED invoices can be refunded.");
    }

    public static function missingApprovedTransaction(int $workOrderId): self
    {
        return new self("No approved transaction ID found to refund for Work Order #{$workOrderId}.");
    }
}