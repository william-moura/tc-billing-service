<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\ValueObjects\InvoiceId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvoiceIdTest extends TestCase
{
    public function test_should_accept_valid_mongodb_object_id(): void
    {
        $id = new InvoiceId('507f1f77bcf86cd799439011');

        $this->assertSame('507f1f77bcf86cd799439011', $id->getValue());
        $this->assertSame('507f1f77bcf86cd799439011', (string) $id);
    }

    public function test_should_accept_valid_uuid(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $id = new InvoiceId($uuid);

        $this->assertSame($uuid, $id->getValue());
    }

    public function test_should_throw_exception_on_empty_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice ID cannot be empty.');

        new InvoiceId('   ');
    }

    public function test_should_throw_exception_on_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid Invoice ID format: 'invalid-id-123'");

        new InvoiceId('invalid-id-123');
    }

    public function test_should_compare_equality_correctly(): void
    {
        $id1 = new InvoiceId('507f1f77bcf86cd799439011');
        $id2 = new InvoiceId('507f1f77bcf86cd799439011');
        $id3 = new InvoiceId('615c1b82e1f3a2c4d5e6f7a8');

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}