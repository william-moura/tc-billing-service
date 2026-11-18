<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\GetInvoiceByWorkOrderUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class GetInvoiceByWorkOrderController extends Controller
{
    public function __construct(
        private readonly GetInvoiceByWorkOrderUseCase $getInvoiceByWorkOrderUseCase
    ) {}

    /**
     * Consulta fatura vinculada a uma Ordem de Serviço.
     *
     * GET /api/v1/invoices/work-order/{workOrderId}
     */
    public function __invoke(int $workOrderId): JsonResponse
    {
        $invoice = $this->getInvoiceByWorkOrderUseCase->execute($workOrderId);

        return response()->json([
            'data' => [
                'id' => $invoice->getId(),
                'work_order_id' => $invoice->getWorkOrderId(),
                'customer_id' => $invoice->getCustomerId(),
                'amount' => $invoice->getAmount()->getAmount(),
                'currency' => $invoice->getAmount()->getCurrency(),
                'status' => $invoice->getStatus()->value,
                'preference_id' => $invoice->getPreferenceId(),
                'payment_url' => $invoice->getPaymentUrl(),
                'attempts' => array_map(
                    static fn ($attempt): array => [
                        'transaction_id' => $attempt->getTransactionId(),
                        'status' => $attempt->getStatus()->value,
                        'created_at' => $attempt->getCreatedAt()->format(DATE_ATOM),
                    ],
                    $invoice->getAttempts()
                ),
                'created_at' => $invoice->getCreatedAt()->format(DATE_ATOM),
            ],
        ], 200);
    }
}
