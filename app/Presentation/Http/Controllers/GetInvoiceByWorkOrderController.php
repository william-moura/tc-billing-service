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

        return response()->json($invoice);
    }
}