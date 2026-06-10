<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function checkout(User $user, ?string $note = null): Order
    {
        $cart = $user->cart()->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw new HttpResponseException(
                response()->json(['message' => 'Cart is empty.'], 422)
            );
        }

        return DB::transaction(function () use ($user, $cart, $note) {
            $total = 0;
            $orderItems = [];

            foreach ($cart->items as $item) {
                $product = $item->product;

                if (! $product || ! $product->is_active) {
                    throw new HttpResponseException(
                        response()->json(['message' => "Product \"{$product?->name}\" is no longer available."], 422)
                    );
                }

                // Lock the product row to prevent race conditions
                $product = \App\Models\Product::lockForUpdate()->find($product->id);

                if ($product->stock < $item->quantity) {
                    throw new HttpResponseException(
                        response()->json(['message' => "Insufficient stock for \"{$product->name}\"."], 422)
                    );
                }

                $product->decrement('stock', $item->quantity);

                $subtotal = $product->price * $item->quantity;
                $total += $subtotal;

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
            throw new HttpResponseException(
                response()->json([
                    'message' => "Cannot transition order from \"{$order->status}\" to \"{$newStatus}\".",
                ], 422)
            );
        }

        $order->update(['status' => $newStatus]);

        return $order;
    }
}
