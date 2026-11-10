<?php

declare(strict_types=1);

namespace App\Application\UseCases;

use App\Application\DTOs\InvoiceOutputDto;
use App\Application\Services\MessagePublisherInterface;
use App\Application\Services\PaymentGatewayInterface;
use App\Domain\Exceptions\InvoiceNotFoundException;
use App\Domain\Exceptions\InvalidPaymentOperationException;
use App\Domain\Repositories\InvoiceRepositoryInterface;
use App\Domain\ValueObjects\PaymentStatus;
use RuntimeException;

final class RefundPaymentUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly MessagePublisherInterface $publisher
    ) {}

    public function execute(int $workOrderId, string$reason): InvoiceOutputDto
    {
        $invoice = $this->repository->findByWorkOrderId($workOrderId);

        if (!$invoice) {
            throw new InvoiceNotFoundException("Invoice for Work Order #{$workOrderId} not found.");
        }

        if ($invoice->getStatus() !== PaymentStatus::APPROVED) {
            throw new InvalidPaymentOperationException(
                "Cannot refund invoice with status {$invoice->getStatus()->value}. Only APPROVED invoices can be refunded."
            );
        }

        // Obtém o ID da transação da última tentativa de pagamento aprovada
        $approvedAttempt = null;
        foreach (array_reverse($invoice->getAttempts()) as$attempt) {
            if ($attempt->getStatus() === PaymentStatus::APPROVED) {
                $approvedAttempt =$attempt;
                break;
            }
        }

        if (!$approvedAttempt) {
            throw new InvalidPaymentOperationException("No approved transaction ID found for Work Order #{$workOrderId}.");
        }

        // Solicita o estorno na API do gateway de pagamento
        $refundSuccessful = $this->paymentGateway->refund($approvedAttempt->getTransactionId());

        if (!$refundSuccessful) {
            throw new RuntimeException("Gateway failed to process refund for transaction {$approvedAttempt->getTransactionId()}.");
        }

        // Atualiza o estado da entidade de domínio
        $invoice->refund($approvedAttempt->getTransactionId());
        $savedInvoice = $this->repository->save($invoice);

        // Notifica o barramento sobre o estorno efetuado (compensação concluída)
        $this->publisher->publish('queue.payment.refunded', [
            'event' => 'payment.refunded',
            'work_order_id' => $savedInvoice->getWorkOrderId(),
            'transaction_id' => $approvedAttempt->getTransactionId(),
            'amount' => $savedInvoice->getAmount()->getAmount(),
            'reason' => $reason,
        ]);

        return InvoiceOutputDto::fromDomain($savedInvoice);
    }
}