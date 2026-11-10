<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\ProcessPaymentWebhookUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PaymentWebhookController extends Controller
{
    public function handle(Request $request, ProcessPaymentWebhookUseCase $useCase): JsonResponse
    {
        // Notificação padrão do Mercado Pago via Webhook
        $type = $request->query('type') ?? $request->input('type');
        $paymentId = $request->query('data_id') ?? $request->input('data.id');

        if ($type === 'payment' && !empty($paymentId)) {
            $useCase->execute((string) $paymentId);
        }

        return response()->json(['status' => 'acknowledged'], 200);
    }
}