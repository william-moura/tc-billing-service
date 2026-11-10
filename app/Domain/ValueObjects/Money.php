<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Money
{
    public function __construct(
        private readonly float $amount,
        private readonly string $currency = 'BRL'
    ) {
        if ($this->amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public function getAmount(): float
    {
        return round($this->amount, 2);
    }

    public function getCurrency(): string
    {
        return strtoupper($this->currency);
    }
}