<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\ValueObjects\Money;

interface PaymentGatewayInterface
{
    /**
     * Gera a preferência de checkout no Mercado Pago.
     * @return array{preference_id: string, init_point: string}
     */
    public function createPreference(int $workOrderId, Money $amount, string $description): array;

    /**
     * Consulta os dados reais da transação recebida no webhook.
     * @return array{status: string, transaction_id: string, external_reference: string}
     */
    public function getPaymentDetails(string $paymentId): array;

    /**
     * Realiza estorno em caso de compensação.
     */
    public function refund(string $paymentId): bool;
}