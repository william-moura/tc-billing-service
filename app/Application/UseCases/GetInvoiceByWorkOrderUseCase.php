<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Application\DTOs\InvoiceOutputDto;

final class GetInvoiceByWorkOrderUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(int $workOrderId): InvoiceOutputDto
    {
        $invoice = $this->invoiceRepository->findByWorkOrderId($workOrderId);

        return InvoiceOutputDto::fromDomain($invoice);
    }
}