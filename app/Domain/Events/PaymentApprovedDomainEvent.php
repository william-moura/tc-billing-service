<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class PaymentApprovedDomainEvent
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly int $customerId,
        public readonly string $transactionId,
        public readonly float $amount,
        public readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {}

    /**
     * Serializa o evento de domínio no formato padrão da fila RabbitMQ (queue.payment.approved).
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'event' => 'payment.approved',
            'work_order_id' => $this->workOrderId,
            'customer_id' => $this->customerId,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'occurred_on' => $this->occurredOn->format(DATE_ATOM),
        ];
    }
}