<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\UseCases\ShowInvoiceUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ShowInvoiceController extends Controller
{
    public function __construct(
        private readonly ShowInvoiceUseCase $useCase
    ) {}

    /**
     * Consulta fatura pelo ID único do documento (MongoDB _id).
     *
     * GET /api/v1/invoices/{id}
     */
    public function __invoke(string $id): JsonResponse
    {
        $invoice = $this->useCase->execute($id);

        return response()->json([
            'data' => $invoice->toArray(),
        ], 200);
    }
    public function show(string $id): JsonResponse
    {
        $invoice = $this->useCase->execute($id);

        return response()->json([
            'data' => $invoice->toArray(),
        ], 200);
    }
}