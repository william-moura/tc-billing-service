<?php

declare(strict_types=1);

namespace App\Application\Services;

interface MessagePublisherInterface
{
    /**
     * Publica mensagens/eventos nas filas do RabbitMQ para orquestração da Saga.
     *
     * @param string $queue Nome da fila destino (ex: queue.payment.approved, queue.payment.failed)
     * @param array<string, mixed> $payload Dados estruturados do evento
     */
    public function publish(string $queue, array $payload): void;
}