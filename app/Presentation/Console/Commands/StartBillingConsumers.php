<?php

declare(strict_types=1);

namespace App\Presentation\Console\Commands;

use App\Infrastructure\Messaging\Consumers\ProductionAbortedConsumer;
use App\Infrastructure\Messaging\Consumers\WorkOrderCreatedConsumer;
use Illuminate\Console\Command;
use Throwable;

final class StartBillingConsumers extends Command
{
    /**
     * Assinatura do comando CLI com opção de filtrar por fila específica.
     * Exemplo: php artisan billing:consume --queue=workorder-created
     *
     * @var string
     */
    protected $signature = 'billing:consume 
                            {--queue=all : The consumer to run (all, workorder-created, production-aborted)}';

    /**
     * Descrição do comando exibida na listagem do Artisan.
     *
     * @var string
     */
    protected $description = 'Starts RabbitMQ consumers for tc-billing-service (WorkOrderCreated and ProductionAborted)';

    public function handle(
        WorkOrderCreatedConsumer $workOrderCreatedConsumer,
        ProductionAbortedConsumer $productionAbortedConsumer
    ): int {
        $queue = (string) $this->option('queue');

        $this->info("Starting RabbitMQ Billing Consumer(s) [Mode: {$queue}]...");

        try {
            match ($queue) {
                'workorder-created' => $this->runWorkOrderCreated($workOrderCreatedConsumer),
                'production-aborted' => $this->runProductionAborted($productionAbortedConsumer),
                'all' => $this->runAll($workOrderCreatedConsumer, $productionAbortedConsumer),
                default => $this->handleInvalidOption($queue),
            };

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Critical error while consuming messages: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function runWorkOrderCreated(WorkOrderCreatedConsumer $consumer): void
    {
        $this->line('<comment>[*] Listening to queue.workorder.created...</comment>');
        $consumer->handle();
    }

    private function runProductionAborted(ProductionAbortedConsumer $consumer): void
    {
        $this->line('<comment>[*] Listening to queue.production.aborted...</comment>');
        $consumer->handle();
    }

    private function runAll(
        WorkOrderCreatedConsumer $workOrderCreatedConsumer,
        ProductionAbortedConsumer $productionAbortedConsumer
    ): void {
        $this->line('<comment>[*] Starting default consumer loop (queue.workorder.created)...</comment>');
        $this->comment('Tip: To run multiple queues simultaneously in production, start individual workers with --queue=...');
        
        $workOrderCreatedConsumer->handle();
    }

    private function handleInvalidOption(string $queue): void
    {
        $this->error("Invalid queue option '{$queue}'. Available options: all, workorder-created, production-aborted.");
    }
}