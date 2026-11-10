<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Invoice;
use App\Domain\Entities\PaymentAttempt;

final class InvoiceOutputDto
{
    /**
     * @param array<array{transaction_id: string, status: string, gateway_message: string|null, created_at: string}> $attempts
     */
    public function __construct(
        public readonly ?string $id,
        public readonly int $workOrderId,
        public readonly int $customerId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $preferenceId,
        public readonly ?string $paymentUrl,
        public readonly array $attempts,
        public readonly string $createdAt
    ) {}

    public static function fromDomain(Invoice $invoice): self
    {
        $attempts = array_map(
            fn(PaymentAttempt $attempt) => [
                'transaction_id' => $attempt->getTransactionId(),
                'status' => $attempt->getStatus()->value,
                'gateway_message' => $attempt->getGatewayMessage(),
                'created_at' => $attempt->getCreatedAt()->format(DATE_ATOM),
            ],
            $invoice->getAttempts()
        );

        return new self(
            id: $invoice->getId(),
            workOrderId: $invoice->getWorkOrderId(),
            customerId: $invoice->getCustomerId(),
            amount: $invoice->getAmount()->getAmount(),
            currency: $invoice->getAmount()->getCurrency(),
            status: $invoice->getStatus()->value,
            preferenceId: $invoice->getPreferenceId(),
            paymentUrl: $invoice->getPaymentUrl(),
            attempts: $attempts,
            createdAt: $invoice->getCreatedAt()->format(DATE_ATOM)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'work_order_id' => $this->workOrderId,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'preference_id' => $this->preferenceId,
            'payment_url' => $this->paymentUrl,
            'attempts' => $this->attempts,
            'created_at' => $this->createdAt,
        ];
    }
}