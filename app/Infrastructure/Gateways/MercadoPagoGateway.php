<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateways;

use App\Application\Services\PaymentGatewayInterface;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MercadoPagoGateway implements PaymentGatewayInterface
{
    private string $accessToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->accessToken = (string) env('MERCADO_PAGO_ACCESS_TOKEN', 'TEST-ACCESS-TOKEN');
        $this->baseUrl = 'https://api.mercadopago.com';
    }

    public function createPreference(int $workOrderId, Money $amount, string $description): array
    {
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", [
                'items' => [
                    [
                        'title' => $description,
                        'quantity' => 1,
                        'unit_price' => $amount->getAmount(),
                        'currency_id' => $amount->getCurrency(),
                    ],
                ],
                'external_reference' => (string) $workOrderId,
                'notification_url' => env('APP_URL') . '/api/v1/payments/webhook',
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Mercado Pago preference creation failed: " . $response->body());
        }

        $data = $response->json();

        return [
            'preference_id' => $data['id'],
            'init_point' => $data['init_point'] ?? $data['sandbox_init_point'],
        ];
    }

    public function getPaymentDetails(string $paymentId): array
    {
        $response = Http::withToken($this->accessToken)->get("{$this->baseUrl}/v1/payments/{$paymentId}");

        if ($response->failed()) {
            throw new RuntimeException("Failed to fetch payment details from Mercado Pago.");
        }

        $data = $response->json();

        return [
            'status' => (string) $data['status'],
            'transaction_id' => (string) $data['id'],
            'external_reference' => (string) ($data['external_reference'] ?? ''),
        ];
    }

    public function refund(string $paymentId): bool
    {
        $response = Http::withToken($this->accessToken)->post("{$this->baseUrl}/v1/payments/{$paymentId}/refunds");
        return $response->successful();
    }
}