<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class InvoiceId
{
    public function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Invoice ID cannot be empty.');
        }

        // Valida formato aceitável: MongoDB ObjectId (24 hex) ou UUID padrão
        $isMongoObjectId = (bool) preg_match('/^[a-f\d]{24}$/i', $trimmed);$isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $trimmed);

        if (!$isMongoObjectId && !$isUuid) {
            throw new InvalidArgumentException("Invalid Invoice ID format: '{$this->value}'. Must be a valid MongoDB ObjectId or UUID.");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value ===$other->getValue();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}