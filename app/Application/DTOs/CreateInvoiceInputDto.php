<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class CreateInvoiceInputDto
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly int $customerId,
        public readonly float $amount,
        public readonly string $currency = 'BRL'
    ) {}

    /**
     * Instancia o DTO a partir de um array associativo (ex: payload vindo da fila RabbitMQ).
     *
     * @param array{work_order_id: int|numeric, customer_id: int|numeric, total_amount: float|numeric, currency?: string} $data
     */
    public static function fromPayload(array $data): self
    {
        return new self(
            workOrderId: (int) $data['work_order_id'],
            customerId: (int) $data['customer_id'],
            amount: (float) $data['total_amount'],
            currency: isset($data['currency']) ? (string) $data['currency'] : 'BRL'
        );
    }

    /**
     * Converte o DTO para array associativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'work_order_id' => $this->workOrderId,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}