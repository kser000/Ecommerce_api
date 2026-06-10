<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Product $product, int $quantity): CartItem
    {
        if (! $product->is_active) {
            throw new HttpResponseException(
                response()->json(['message' => 'Product is not available.'], 422)
            );
        }

        if ($product->stock < $quantity) {
            throw new HttpResponseException(
                response()->json(['message' => 'Insufficient stock.'], 422)
            );
        }

        $cart = $this->getOrCreateCart($user);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;

            if ($product->stock < $newQty) {
                throw new HttpResponseException(
                    response()->json(['message' => 'Insufficient stock.'], 422)
                );
            }

            $item->update(['quantity' => $newQty]);
        } else {
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $quantity,
            ]);
        }

        return $item->load('product');
    }

    public function updateItem(User $user, CartItem $item, int $quantity): CartItem
    {
        $this->authorizeItem($user, $item);

        if ($item->product->stock < $quantity) {
            throw new HttpResponseException(
                response()->json(['message' => 'Insufficient stock.'], 422)
            );
        }

        $item->update(['quantity' => $quantity]);

        return $item->load('product');
    }

    public function removeItem(User $user, CartItem $item): void
    {
        $this->authorizeItem($user, $item);
        $item->delete();
    }

    public function clearCart(User $user): void
    {
        $cart = $user->cart;
        $cart?->items()->delete();
    }

    private function authorizeItem(User $user, CartItem $item): void
    {
        if ($item->cart->user_id !== $user->id) {
            throw new HttpResponseException(
                response()->json(['message' => 'Forbidden.'], 403)
            );
        }
    }
}
