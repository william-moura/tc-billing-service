<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation;

use App\Infrastructure\Messaging\Consumers\ProductionAbortedConsumer;
use App\Infrastructure\Messaging\Consumers\WorkOrderCreatedConsumer;
use App\Presentation\Console\Commands\StartBillingConsumers;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class StartBillingConsumersTest extends TestCase
{
    public function test_should_call_work_order_created_consumer_when_selected(): void
    {
        $createdConsumer = $this->createMock(WorkOrderCreatedConsumer::class);
        $createdConsumer->expects($this->once())->method('handle');

        $abortedConsumer = $this->createMock(ProductionAbortedConsumer::class);
        $abortedConsumer->expects($this->never())->method('handle');

        $command = new StartBillingConsumers();
        $input = new ArrayInput(['--queue' => 'workorder-created']);
        $output = new BufferedOutput();

        $command->setInput($input);
        $command->setOutput(new \Illuminate\Console\OutputStyle($input, $output));

        $exitCode = $command->handle($createdConsumer, $abortedConsumer);

        $this->assertSame(0, $exitCode);
    }

    public function test_should_call_production_aborted_consumer_when_selected(): void
    {
        $createdConsumer = $this->createMock(WorkOrderCreatedConsumer::class);
        $createdConsumer->expects($this->never())->method('handle');

        $abortedConsumer = $this->createMock(ProductionAbortedConsumer::class);
        $abortedConsumer->expects($this->once())->method('handle');

        $command = new StartBillingConsumers();
        $input = new ArrayInput(['--queue' => 'production-aborted']);
        $output = new BufferedOutput();

        $command->setInput($input);
        $command->setOutput(new \Illuminate\Console\OutputStyle($input, $output));

        $exitCode = $command->handle($createdConsumer, $abortedConsumer);

        $this->assertSame(0, $exitCode);
    }
}