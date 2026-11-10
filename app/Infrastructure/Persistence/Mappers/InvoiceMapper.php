<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mappers;

use App\Domain\Entities\Invoice;
use App\Domain\Entities\PaymentAttempt;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\PaymentStatus;
use App\Infrastructure\Persistence\Models\InvoiceMongoModel;
use DateTimeImmutable;

final class InvoiceMapper
{
    public static function toDomain(InvoiceMongoModel $model): Invoice
    {
        $attempts = array_map(
            fn(array $att) => new PaymentAttempt(
                transactionId: $att['transaction_id'],
                status: PaymentStatus::from($att['status']),
                gatewayMessage: $att['gateway_message'] ?? null,
                createdAt: new DateTimeImmutable($att['created_at'])
            ),
            $model->attempts ?? []
        );

        return new Invoice(
            id: (string) $model->_id,
            workOrderId: (int) $model->work_order_id,
            customerId: (int) $model->customer_id,
            amount: new Money((float) $model->amount, (string) ($model->currency ?? 'BRL')),
            status: PaymentStatus::from($model->status),
            preferenceId: $model->preference_id,
            paymentUrl: $model->payment_url,
            attempts: $attempts,
            createdAt: new DateTimeImmutable($model->created_at?->toIso8601String() ?? 'now')
        );
    }

    public static function toDocument(Invoice $invoice): array
    {
        return [
            'work_order_id' => $invoice->getWorkOrderId(),
            'customer_id' => $invoice->getCustomerId(),
            'amount' => $invoice->getAmount()->getAmount(),
            'currency' => $invoice->getAmount()->getCurrency(),
            'status' => $invoice->getStatus()->value,
            'preference_id' => $invoice->getPreferenceId(),
            'payment_url' => $invoice->getPaymentUrl(),
            'attempts' => array_map(fn(PaymentAttempt $att) => [
                'transaction_id' => $att->getTransactionId(),
                'status' => $att->getStatus()->value,
                'gateway_message' => $att->getGatewayMessage(),
                'created_at' => $att->getCreatedAt()->format(DATE_ATOM),
            ], $invoice->getAttempts()),
        ];
    }
}