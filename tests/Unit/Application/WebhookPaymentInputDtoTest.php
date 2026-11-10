<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\DTOs\WebhookPaymentInputDto;
use PHPUnit\Framework\TestCase;

final class WebhookPaymentInputDtoTest extends TestCase
{
    public function test_should_create_dto_from_standard_json_payload(): void
    {
        $payload = [
            'action' => 'payment.created',
            'type' => 'payment',
            'data' => [
                'id' => '1234567890',
            ],
            'live_mode' => true,
        ];

        $dto = WebhookPaymentInputDto::fromRequestData($payload);

        $this->assertNotNull($dto);
        $this->assertSame('1234567890', $dto->paymentId);
        $this->assertSame('payment', $dto->type);
        $this->assertSame('payment.created', $dto->action);
        $this->assertTrue($dto->isPaymentNotification());
    }

    public function test_should_create_dto_from_query_params(): void
    {
        $queryParams = [
            'data_id' => '987654321',
            'type' => 'payment',
        ];

        $dto = WebhookPaymentInputDto::fromRequestData([], $queryParams);

        $this->assertNotNull($dto);
        $this->assertSame('987654321', $dto->paymentId);
        $this->assertSame('payment', $dto->type);
    }

    public function test_should_return_null_when_no_payment_id_present(): void
    {
        $dto = WebhookPaymentInputDto::fromRequestData(['random' => 'value']);

        $this->assertNull($dto);
    }
}