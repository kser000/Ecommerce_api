<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function checkout(User $user, ?string $note = null): Order
    {
        $cart = $user->cart()->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw new BusinessException('Cart is empty.');
        }

        return DB::transaction(function () use ($user, $cart, $note) {
            $total      = '0';
            $orderItems = [];

            foreach ($cart->items as $item) {
                $product = $item->product;

                if (! $product || ! $product->is_active) {
                    throw new BusinessException("Product \"{$product?->name}\" is no longer available.");
                }

                $product = Product::lockForUpdate()->findOrFail($product->id);

                if ($product->stock < $item->quantity) {
                    throw new BusinessException("Insufficient stock for \"{$product->name}\".");
                }

                $product->decrement('stock', $item->quantity);

                $subtotal = bcmul((string) $product->price, (string) $item->quantity, 2);
                $total    = bcadd($total, $subtotal, 2);

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $product->price,
                    'quantity'     => $item->quantity,
                ];
            }

            $order = Order::create([
                'user_id'      => $user->id,
                'status'       => 'pending',
                'total_amount' => $total,
                'note'         => $note,
            ]);

            $order->items()->createMany($orderItems);

            $cart->items()->delete();

            return $order->load('items');
        });
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        if (! $order->canTransitionTo($newStatus)) {
            throw new BusinessException(
                "Cannot transition order from \"{$order->status}\" to \"{$newStatus}\"."
            );
        }

        $order->update(['status' => $newStatus]);

        return $order;
    }
}
