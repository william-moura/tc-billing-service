<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Publishers;

use App\Application\Services\MessagePublisherInterface;
use App\Infrastructure\Messaging\RabbitMQService;
use Illuminate\Support\Facades\Log;

final class BillingRabbitMQPublisher implements MessagePublisherInterface
{
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    public function publish(string $queue, array $payload): void
    {
        Log::info("BillingRabbitMQPublisher: Publishing message to queue '{$queue}'.", [
            'queue' => $queue,
            'event' => $payload['event'] ?? 'unknown',
            'work_order_id' => $payload['work_order_id'] ?? null,
        ]);

        $this->rabbitMQService->publish($queue, $payload);
    }
}