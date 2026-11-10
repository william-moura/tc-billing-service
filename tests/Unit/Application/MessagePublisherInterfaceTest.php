<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Services\MessagePublisherInterface;
use PHPUnit\Framework\TestCase;

final class MessagePublisherInterfaceTest extends TestCase
{
    public function test_should_verify_publisher_contract(): void
    {
        $mockPublisher = $this->createMock(MessagePublisherInterface::class);

        $mockPublisher->expects($this->once())
            ->method('publish')
            ->with(
                'queue.payment.approved',
                [
                    'event' => 'payment.approved',
                    'work_order_id' => 10,
                ]
            );

        $mockPublisher->publish('queue.payment.approved', [
            'event' => 'payment.approved',
            'work_order_id' => 10,
        ]);
    }
}