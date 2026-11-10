<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Application\DTOs\InvoiceOutputDto;
use App\Application\UseCases\RefundPaymentUseCase;
use App\Infrastructure\Messaging\Consumers\ProductionAbortedConsumer;
use App\Infrastructure\Messaging\RabbitMQService;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

final class ProductionAbortedConsumerTest extends TestCase
{
    public function test_should_process_and_acknowledge_message_on_success(): void
    {
        $rabbitMQService = $this->createMock(RabbitMQService::class);
        $refundPaymentUseCase = $this->createMock(RefundPaymentUseCase::class);

        $payload = json_encode([
            'event' => 'production.aborted',
            'work_order_id' => 77,
            'reason' => 'Mechanic unavailable',
        ], JSON_THROW_ON_ERROR);

        $message = $this->createMock(AMQPMessage::class);
        $message->method('getBody')->willReturn($payload);
        $message->expects($this->once())->method('ack');
        $message->expects($this->never())->method('nack');

        $refundPaymentUseCase->expects($this->once())
            ->method('execute')
            ->with(77, 'Mechanic unavailable')
            ->willReturn($this->createMock(InvoiceOutputDto::class));

        $rabbitMQService->expects($this->once())
            ->method('consume')
            ->with('queue.production.aborted', $this->anything())
            ->willReturnCallback(function (string $queue, callable $callback) use ($message): void {
                $callback($message);
            });

        $consumer = new ProductionAbortedConsumer($rabbitMQService, $refundPaymentUseCase);
        $consumer->handle();
    }

    public function test_should_nack_message_when_use_case_throws_exception(): void
    {
        $rabbitMQService = $this->createMock(RabbitMQService::class);
        $refundPaymentUseCase = $this->createMock(RefundPaymentUseCase::class);

        $payload = json_encode([
            'work_order_id' => 88,
            'reason' => 'Spare parts out of stock',
        ], JSON_THROW_ON_ERROR);

        $message = $this->createMock(AMQPMessage::class);
        $message->method('getBody')->willReturn($payload);
        $message->expects($this->never())->method('ack');
        $message->expects($this->once())->method('nack')->with(false);

        $refundPaymentUseCase->expects($this->once())
            ->method('execute')
            ->with(88, 'Spare parts out of stock')
            ->willThrowException(new Exception('Database connectivity issue'));

        $rabbitMQService->expects($this->once())
            ->method('consume')
            ->with('queue.production.aborted', $this->anything())
            ->willReturnCallback(function (string $queue, callable $callback) use ($message): void {
                $callback($message);
            });

        $consumer = new ProductionAbortedConsumer($rabbitMQService, $refundPaymentUseCase);
        $consumer->handle();
    }
}