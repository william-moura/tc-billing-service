<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\InvoiceOutputDto;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Repositories\InvoiceRepositoryInterface;

final class ShowInvoiceUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository
    ) {}

    public function execute(string $id): InvoiceOutputDto
    {
        $invoice = $this->repository->findById($id);

        if ($invoice === null) {
            throw InvoiceNotFoundException::forId($id);
        }

        return InvoiceOutputDto::fromDomain($invoice);
    }
}
