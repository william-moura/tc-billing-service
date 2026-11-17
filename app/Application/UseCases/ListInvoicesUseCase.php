<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\InvoiceOutputDto;
use App\Domain\Entities\Invoice;
use App\Domain\Repositories\InvoiceRepositoryInterface;

final class ListInvoicesUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    public function execute(): array
    {
        $invoices = $this->repository->findAll();
        
        $invoices = array_map(
            fn($invoice) => InvoiceOutputDto::fromDomain($invoice)->toArray(),
            $this->repository->findAll()
        );

        return $invoices;
    }
}