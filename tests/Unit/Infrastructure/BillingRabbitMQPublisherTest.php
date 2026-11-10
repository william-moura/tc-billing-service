<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Messaging\Publishers\BillingRabbitMQPublisher;
use App\Infrastructure\Messaging\RabbitMQService;
use PHPUnit\Framework\TestCase;

final class BillingRabbitMQPublisherTest extends TestCase
{
    public function test_should_delegate_publish_call_to_rabbitmq_service(): void
    {
        $rabbitMQService = $this->createMock(RabbitMQService::class);

        $payload = [
            'event' => 'payment.approved',
            'work_order_id' => 10,
            'amount' => 150.00,
        ];

        $rabbitMQService->expects($this->once())
            ->method('publish')
            ->with('queue.payment.approved', $payload);

        $publisher = new BillingRabbitMQPublisher($rabbitMQService);
        $publisher->publish('queue.payment.approved', $payload);
    }
}