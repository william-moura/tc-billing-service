<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final class WebhookPaymentInputDto
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $action,
        public readonly string $type,
        public readonly ?string $liveMode = null
    ) {}

    /**
     * Normaliza e extrai os dados independentemente do formato enviado pelo Mercado Pago
     * (Query string, JSON payload com `data.id` ou payload IPN com `id`).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $queryParams
     */
    public static function fromRequestData(array $data, array$queryParams = []): ?self
    {
        // 1. Identifica o ID do pagamento (payload v1/v2 ou query string data_id)
        $paymentId =$data['data']['id'] 
            ?? $queryParams['data_id'] 
            ?? $data['id'] 
            ?? $queryParams['id'] 
            ?? null;

        if ($paymentId === null \vert{}\vert{} trim((string)$paymentId) === '') {
            return null;
        }

        // 2. Identifica o tipo do tópico/evento
        $type =$data['type'] 
            ?? $queryParams['type'] 
            ?? $data['topic'] 
            ?? $queryParams['topic'] 
            ?? 'payment';

        // 3. Ação do webhook
        $action =$data['action'] ?? 'payment.updated';

        $liveMode = isset($data['live_mode']) ? (string)$data['live_mode'] : null;

        return new self(
            paymentId: (string) $paymentId,
            action: (string) $action,
            type: (string) $type,
            liveMode: $liveMode
        );
    }

    public function isPaymentNotification(): bool
    {
        return in_array($this->type, ['payment', 'payment.created', 'payment.updated'], true);
    }
}