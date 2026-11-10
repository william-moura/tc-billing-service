<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Events\PaymentFailedDomainEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PaymentFailedDomainEventTest extends TestCase
{
    public function test_should_instantiate_and_generate_valid_compensation_payload(): void
    {
        $now = new DateTimeImmutable('2026-11-10T14:30:00+00:00');

        $event = new PaymentFailedDomainEvent(
            workOrderId: 202,
            reason: 'Insufficient funds on credit card',
            transactionId: 'mp-tx-failed-1122',
            occurredOn: $now
        );

        $payload = $event->toPayload();

        $this->assertSame(202, $event->workOrderId);
        $this->assertSame('Insufficient funds on credit card', $event->reason);
        $this->assertSame('mp-tx-failed-1122', $event->transactionId);
        $this->assertSame('payment.failed', $payload['event']);
        $this->assertSame(202, $payload['work_order_id']);
        $this->assertSame('Insufficient funds on credit card', $payload['reason']);
        $this->assertSame('mp-tx-failed-1122', $payload['transaction_id']);
        $this->assertSame('2026-11-10T14:30:00+00:00', $payload['occurred_on']);
    }

    public function test_should_allow_null_transaction_id_when_payment_was_not_created(): void
    {
        $event = new PaymentFailedDomainEvent(
            workOrderId: 303,
            reason: 'Customer cancelled checkout flow'
        );

        $payload = $event->toPayload();

        $this->assertNull($event->transactionId);
        $this->assertNull($payload['transaction_id']);
        $this->assertSame(303, $payload['work_order_id']);
    }
}