<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'idempotency_key',
        'status',
        'gateway_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
