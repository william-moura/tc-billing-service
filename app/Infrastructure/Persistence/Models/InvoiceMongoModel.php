<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use MongoDB\Laravel\Eloquent\Model;

class InvoiceMongoModel extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'invoices';

    protected $fillable = [
        'work_order_id',
        'customer_id',
        'amount',
        'currency',
        'status',
        'preference_id',
        'payment_url',
        'attempts',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'customer_id' => 'integer',
        'amount' => 'float',
        'attempts' => 'array',
    ];
}