<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\InvoiceOutputDto;
use App\Domain\Entities\Invoice;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Repositories\InvoiceRepositoryInterface;

final class GetInvoiceByWorkOrderUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $workOrderId): Invoice
    {
        $invoice = $this->invoiceRepository->findByWorkOrderId($workOrderId);
        if ($invoice === null) {
            throw InvoiceNotFoundException::forWorkOrder($workOrderId);
        }
        return $invoice;
    }
}
