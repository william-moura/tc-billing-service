<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\PaymentStatus;
use DateTimeImmutable;

final class PaymentAttempt
{
    public function __construct(
        private readonly string $transactionId,
        private readonly PaymentStatus $status,
        private readonly ?string $gatewayMessage = null,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable()
    ) {}

    public function getTransactionId(): string { return $this->transactionId; }
    public function getStatus(): PaymentStatus { return $this->status; }
    public function getGatewayMessage(): ?string { return $this->gatewayMessage; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}