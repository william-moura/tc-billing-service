<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Invoice;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): Invoice;
    public function findById(string $id): ?Invoice;
    public function findByWorkOrderId(int $workOrderId): ?Invoice;
    public function findByPreferenceId(string $preferenceId): ?Invoice;
}