<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Invoice;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Infrastructure\Persistence\Mappers\InvoiceMapper;
use App\Infrastructure\Persistence\Models\InvoiceMongoModel;

final class InvoiceMongoRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): Invoice
    {
        $documentData = InvoiceMapper::toDocument($invoice);

        if ($invoice->getId() !== null) {
            $model = InvoiceMongoModel::find($invoice->getId());

            if ($model !== null) {
                $model->update($documentData);
                return InvoiceMapper::toDomain($model->fresh());
            }
        }

        /** @var InvoiceMongoModel $model */
        $model = InvoiceMongoModel::create($documentData);

        return InvoiceMapper::toDomain($model);
    }

    public function findById(string $id): ?Invoice
    {
        /** @var InvoiceMongoModel|null $model */
        $model = InvoiceMongoModel::find($id);

        return $model !== null ? InvoiceMapper::toDomain($model) : null;
    }

    public function findByWorkOrderId(int $workOrderId): ?Invoice
    {
        /** @var InvoiceMongoModel|null $model */
        $model = InvoiceMongoModel::where('work_order_id', $workOrderId)->first();

        return $model !== null ? InvoiceMapper::toDomain($model) : null;
    }

    public function findByPreferenceId(string $preferenceId): ?Invoice
    {
        /** @var InvoiceMongoModel|null $model */
        $model = InvoiceMongoModel::where('preference_id', $preferenceId)->first();

        return $model !== null ? InvoiceMapper::toDomain($model) : null;
    }
}