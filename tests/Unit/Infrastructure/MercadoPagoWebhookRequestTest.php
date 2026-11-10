<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Presentation\Http\Requests\MercadoPagoWebhookRequest;
use PHPUnit\Framework\TestCase;

final class MercadoPagoWebhookRequestTest extends TestCase
{
    public function test_should_authorize_request(): void
    {
        $request = new MercadoPagoWebhookRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_should_contain_expected_validation_rules(): void
    {
        $request = new MercadoPagoWebhookRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('topic', $rules);
        $this->assertArrayHasKey('data.id', $rules);
        $this->assertArrayHasKey('data_id', $rules);
    }

    public function test_to_dto_should_build_valid_dto_from_json_body(): void
    {
        $request = new MercadoPagoWebhookRequest();
        $request->initialize(
            request: [
                'type' => 'payment',
                'action' => 'payment.created',
                'data' => ['id' => '99887766'],
            ]
        );

        $dto = $request->toDto();

        $this->assertNotNull($dto);
        $this->assertSame('99887766', $dto->paymentId);
        $this->assertSame('payment', $dto->type);
        $this->assertTrue($dto->isPaymentNotification());
    }

    public function test_to_dto_should_build_valid_dto_from_query_params(): void
    {
        $request = new MercadoPagoWebhookRequest();
        $request->initialize(
            query: [
                'type' => 'payment',
                'data_id' => '11223344',
            ]
        );

        $dto = $request->toDto();

        $this->assertNotNull($dto);
        $this->assertSame('11223344', $dto->paymentId);
        $this->assertSame('payment', $dto->type);
    }
}