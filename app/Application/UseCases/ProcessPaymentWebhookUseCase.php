<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\Services\MessagePublisherInterface;
use App\Application\Services\PaymentGatewayInterface;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Repositories\InvoiceRepositoryInterface;

final class ProcessPaymentWebhookUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly MessagePublisherInterface $publisher
    ) {}

    public function execute(string $paymentId): void
    {
        $paymentData = $this->paymentGateway->getPaymentDetails($paymentId);
        $workOrderId = (int) $paymentData['external_reference'];

        $invoice = $this->repository->findByWorkOrderId($workOrderId);
        if (!$invoice) {
            throw new InvoiceNotFoundException("Invoice for Work Order #{$workOrderId} not found.");
        }

        if ($paymentData['status'] === 'approved') {
            $invoice->markAsApproved($paymentData['transaction_id']);
            $this->repository->save($invoice);

            // Happy Path: dispara avanço para a oficina mecânica
            $this->publisher->publish('queue.payment.approved', [
                'event' => 'payment.approved',
                'work_order_id' => $invoice->getWorkOrderId(),
                'customer_id' => $invoice->getCustomerId(),
                'transaction_id' => $paymentData['transaction_id'],
                'amount' => $invoice->getAmount()->getAmount(),
            ]);
            return;
        }

        if (in_array($paymentData['status'], ['rejected', 'cancelled'], true)) {
            $invoice->markAsRejected($paymentData['transaction_id'], "Gateway status: {$paymentData['status']}");
            $this->repository->save($invoice);

            // Saga Rollback / Compensação: notifica o os-service para cancelar a OS
            $this->publisher->publish('queue.payment.failed', [
                'event' => 'payment.failed',
                'work_order_id' => $invoice->getWorkOrderId(),
                'reason' => "Payment {$paymentData['status']} by Mercado Pago",
            ]);
        }
    }
}