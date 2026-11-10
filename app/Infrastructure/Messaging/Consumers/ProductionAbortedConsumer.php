<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Consumers;

use App\Application\UseCases\RefundPaymentUseCase;
use App\Infrastructure\Messaging\RabbitMQService;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

final class ProductionAbortedConsumer
{
    private const QUEUE_NAME = 'queue.production.aborted';

    public function __construct(
        private readonly RabbitMQService $rabbitMQService,
        private readonly RefundPaymentUseCase $refundPaymentUseCase
    ) {}

    public function handle(): void
    {
        $this->rabbitMQService->consume(self::QUEUE_NAME, function (AMQPMessage $message): void {
            try {
                $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

                $workOrderId = isset($payload['work_order_id']) ? (int) $payload['work_order_id'] : null;
                $reason = $payload['reason'] ?? 'Production aborted without specific reason';

                if ($workOrderId === null || $workOrderId <= 0) {
                    Log::warning('ProductionAbortedConsumer: Invalid or missing work_order_id in payload.', [
                        'payload' => $payload,
                    ]);
                    $message->ack();
                    return;
                }

                Log::info("ProductionAbortedConsumer: Triggering refund compensation for Work Order #{$workOrderId}.", [
                    'reason' => $reason,
                ]);

                $this->refundPaymentUseCase->execute($workOrderId, (string) $reason);

                Log::info("ProductionAbortedConsumer: Refund compensation processed successfully for Work Order #{$workOrderId}.");
                $message->ack();
            } catch (Exception $e) {
                Log::error("ProductionAbortedConsumer: Error processing compensation: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);

                // Rejeita a mensagem sem reenfileirar indefinidamente para evitar loops tóxicos
                $message->nack(requeue: false);
            }
        });
    }
}