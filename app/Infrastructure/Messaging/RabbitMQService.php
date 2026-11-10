<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host = '',
        private readonly int $port = 5672,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
        private readonly string $vhost = '/'
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            host: (string) env('RABBITMQ_HOST', 'rabbitmq'),
            port: (int) env('RABBITMQ_PORT', 5672),
            user: (string) env('RABBITMQ_USER', 'guest'),
            password: (string) env('RABBITMQ_PASSWORD', 'guest'),
            vhost: (string) env('RABBITMQ_VHOST', '/')
        );
    }

    public function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            if ($this->connection === null || !$this->connection->isConnected()) {
                $this->connection = new AMQPStreamConnection(
                    $this->host,
                    $this->port,
                    $this->user,
                    $this->password,
                    $this->vhost
                );
            }

            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    /**
     * Declara uma fila persistente e publica o payload em formato JSON.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(string $queue, array $payload): void
    {
        $channel = $this->getChannel();

        $channel->queue_declare(
            queue: $queue,
            passive: false,
            durable: true,
            exclusive: false,
            auto_delete: false
        );

        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($message, '', $queue);
    }

    /**
     * Consome mensagens da fila informada de forma contínua com prefetch count 1.
     */
    public function consume(string $queue, Closure $callback): void
    {
        $channel = $this->getChannel();

        $channel->queue_declare(
            queue: $queue,
            passive: false,
            durable: true,
            exclusive: false,
            auto_delete: false
        );

        $channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);

        $channel->basic_consume(
            queue: $queue,
            consumer_tag: '',
            no_local: false,
            no_ack: false,
            exclusive: false,
            nowait: false,
            callback: $callback
        );

        while ($channel->is_consuming()) {
            try {
                $channel->wait();
            } catch (Exception $e) {
                Log::error("RabbitMQ consume error on queue '{$queue}': {$e->getMessage()}");
                break;
            }
        }
    }

    public function close(): void
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            $this->channel->close();
        }

        if ($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}