<?php

declare(strict_types=1);

namespace App\Domain\Events;

use DateTimeImmutable;

final class PaymentFailedDomainEvent
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly string $reason,
        public readonly ?string $transactionId = null,
        public readonly DateTimeImmutable $occurredOn = new DateTimeImmutable()
    ) {}

    /**
     * Serializa o evento de domínio no formato padrão para a fila de compensação do RabbitMQ (queue.payment.failed).
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'event' => 'payment.failed',
            'work_order_id' => $this->workOrderId,
            'reason' => $this->reason,
            'transaction_id' => $this->transactionId,
            'occurred_on' => $this->occurredOn->format(DATE_ATOM),
        ];
    }
}