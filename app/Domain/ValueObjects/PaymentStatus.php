<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case REFUNDED = 'REFUNDED';

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::REFUNDED], true);
    }
}