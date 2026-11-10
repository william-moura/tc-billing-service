<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Consumers;

use App\Application\DTOs\CreateInvoiceInputDto;
use App\Application\UseCases\CreateInvoiceUseCase;
use App\Infrastructure\Messaging\RabbitMQService;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

final class WorkOrderCreatedConsumer
{
    public function __construct(
        private readonly RabbitMQService $rabbitMQService,
        private readonly CreateInvoiceUseCase $createInvoiceUseCase
    ) {}

    public function handle(): void
    {
        $this->rabbitMQService->consume('queue.workorder.created', function (AMQPMessage $msg): void {
            try {
                $payload = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);

                $dto = new CreateInvoiceInputDto(
                    workOrderId: (int) $payload['work_order_id'],
                    customerId: (int) $payload['customer_id'],
                    amount: (float) $payload['total_amount']
                );

                $this->createInvoiceUseCase->execute($dto);

                Log::info("Invoice generated for Work Order #{$payload['work_order_id']}");
                $msg->ack();
            } catch (Exception $e) {
                Log::error("Failed to process workorder.created in billing: {$e->getMessage()}");
                $msg->nack(false, false);
            }
        });
    }
}