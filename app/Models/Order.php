<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'status', 'total_amount', 'note'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = [
            'pending'   => ['paid', 'cancelled'],
            'paid'      => ['shipped', 'cancelled'],
            'shipped'   => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        return in_array($newStatus, $allowed[$this->status] ?? []);
    }
}
