<?php

namespace App\Infrastructure\Gateways;

use App\Application\Services\PaymentGatewayInterface;
use App\Domain\ValueObjects\Money;

final class FakePaymentGateway implements PaymentGatewayInterface
{
    public function createPreference(int $workOrderId, Money $amount, string $description): array
    {
        $preferenceId = 'pref_test_' . uniqid();
        $checkoutUrl = 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=' . $preferenceId;
        return [
            'id' => $preferenceId,
            'preference_id' => $preferenceId,
            'init_point' => $checkoutUrl,
            'sandbox_init_point' => $checkoutUrl,
            'payment_url' => $checkoutUrl,
            'external_reference' => $workOrderId,
        ];
    }

    public function getPaymentDetails(string $paymentId): array
    {
        return [
            'id' => $paymentId,
            'status' => 'approved',
            'external_reference' => 1,
            'transaction_id' => '1234567890',
        ];
    }
    
    public function refund(string $paymentId): bool
    {
        return true;
    }
}