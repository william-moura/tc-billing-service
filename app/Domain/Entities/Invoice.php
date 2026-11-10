<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InvalidPaymentOperationException;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\PaymentStatus;
use DateTimeImmutable;

final class Invoice
{
    /** @param PaymentAttempt[] $attempts */
    public function __construct(
        private ?string $id,
        private readonly int $workOrderId,
        private readonly int $customerId,
        private readonly Money $amount,
        private PaymentStatus $status,
        private ?string $preferenceId = null,
        private ?string $paymentUrl = null,
        private array $attempts = [],
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable()
    ) {}

    public static function create(int $workOrderId, int $customerId, Money $amount): self
    {
        return new self(
            id: null,
            workOrderId: $workOrderId,
            customerId: $customerId,
            amount: $amount,
            status: PaymentStatus::PENDING
        );
    }

    public function attachGatewayPreference(string $preferenceId, string $paymentUrl): void
    {
        $this->preferenceId = $preferenceId;
        $this->paymentUrl = $paymentUrl;
    }

    public function markAsApproved(string $transactionId): void
    {
        if ($this->status === PaymentStatus::APPROVED) {
            return;
        }

        $this->status = PaymentStatus::APPROVED;
        $this->attempts[] = new PaymentAttempt($transactionId, PaymentStatus::APPROVED, 'Payment successful');
    }

    public function markAsRejected(string $transactionId, string $reason): void
    {
        if ($this->status === PaymentStatus::APPROVED) {
            throw new InvalidPaymentOperationException('Cannot reject an already approved invoice.');
        }

        $this->status = PaymentStatus::REJECTED;
        $this->attempts[] = new PaymentAttempt($transactionId, PaymentStatus::REJECTED, $reason);
    }

    public function refund(string $transactionId): void
    {
        if ($this->status !== PaymentStatus::APPROVED) {
            throw new InvalidPaymentOperationException('Only approved invoices can be refunded.');
        }

        $this->status = PaymentStatus::REFUNDED;
        $this->attempts[] = new PaymentAttempt($transactionId, PaymentStatus::REFUNDED, 'Compensating transaction: Refund');
    }

    public function getId(): ?string { return $this->id; }
    public function getWorkOrderId(): int { return $this->workOrderId; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getAmount(): Money { return $this->amount; }
    public function getStatus(): PaymentStatus { return $this->status; }
    public function getPreferenceId(): ?string { return $this->preferenceId; }
    public function getPaymentUrl(): ?string { return $this->paymentUrl; }
    public function getAttempts(): array { return $this->attempts; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}