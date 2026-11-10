<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Events\PaymentApprovedDomainEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PaymentApprovedDomainEventTest extends TestCase
{
    public function test_should_instantiate_and_generate_valid_payload(): void
    {
        $now = new DateTimeImmutable('2026-11-10T14:00:00+00:00');

        $event = new PaymentApprovedDomainEvent(
            workOrderId: 101,
            customerId: 5,
            transactionId: 'mp-tx-998877',
            amount: 250.75,
            occurredOn: $now
        );

        $payload = $event->toPayload();

        $this->assertSame(101, $event->workOrderId);
        $this->assertSame(5, $event->customerId);
        $this->assertSame('mp-tx-998877', $event->transactionId);
        $this->assertSame(250.75, $event->amount);
        $this->assertSame('payment.approved', $payload['event']);
        $this->assertSame(101, $payload['work_order_id']);
        $this->assertSame('2026-11-10T14:00:00+00:00', $payload['occurred_on']);
    }
}