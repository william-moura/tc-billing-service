<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\ProcessPaymentWebhookUseCase;
use App\Presentation\Http\Requests\MercadoPagoWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PaymentWebhookController extends Controller
{
    public function handle(MercadoPagoWebhookRequest $request, ProcessPaymentWebhookUseCase $useCase): JsonResponse
    {
        $dto = $request->toDto();

        if ($dto !== null && $dto->isPaymentNotification()) {
            $useCase->execute($dto->paymentId);
        }

        return response()->json([
            'status' => 'acknowledged',
        ], 200);
    }
}