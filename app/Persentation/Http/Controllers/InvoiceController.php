<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\DTOs\InvoiceOutputDto;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    /**
     * Consulta fatura pelo ID único do documento (MongoDB _id).
     *
     * GET /api/v1/invoices/{id}
     */
    public function show(string $id): JsonResponse
    {
        $invoice = $this->repository->findById($id);

        if ($invoice === null) {
            throw InvoiceNotFoundException::forId($id);
        }

        $dto = InvoiceOutputDto::fromDomain($invoice);

        return response()->json([
            'data' => $dto->toArray(),
        ], 200);
    }

    /**
     * Consulta fatura vinculada a uma Ordem de Serviço.
     *
     * GET /api/v1/invoices/work-order/{workOrderId}
     */
    public function findByWorkOrder(int $workOrderId): JsonResponse
    {
        $invoice = $this->repository->findByWorkOrderId($workOrderId);

        if ($invoice === null) {
            throw InvoiceNotFoundException::forWorkOrder($workOrderId);
        }

        $dto = InvoiceOutputDto::fromDomain($invoice);

        return response()->json([
            'data' => $dto->toArray(),
        ], 200);
    }
}