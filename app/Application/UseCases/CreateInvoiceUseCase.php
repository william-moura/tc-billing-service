<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\CreateInvoiceInputDto;
use App\Application\Services\PaymentGatewayInterface;
use App\Domain\Entities\Invoice;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Domain\ValueObjects\Money;

final class CreateInvoiceUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly PaymentGatewayInterface $paymentGateway
    ) {}

    public function execute(CreateInvoiceInputDto $input): Invoice
    {
        $existing = $this->repository->findByWorkOrderId($input->workOrderId);
        if ($existing !== null) {
            return $existing;
        }

        $money = new Money($input->amount);
        $invoice = Invoice::create($input->workOrderId, $input->customerId, $money);

        // Gera o link de pagamento no Mercado Pago
        $preference = $this->paymentGateway->createPreference(
            workOrderId: $input->workOrderId,
            amount: $money,
            description: "Work Order #{$input->workOrderId}"
        );

        $invoice->attachGatewayPreference(
            preferenceId: $preference['preference_id'],
            paymentUrl: $preference['init_point']
        );

        return $this->repository->save($invoice);
    }
}