<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Product $product, int $quantity): CartItem
    {
        if (! $product->is_active) {
            throw new BusinessException('Product is not available.');
        }

        if ($product->stock < $quantity) {
            throw new BusinessException('Insufficient stock.');
        }

        $cart = $this->getOrCreateCart($user);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;

            if ($product->stock < $newQty) {
                throw new BusinessException('Insufficient stock.');
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
        if ($item->product->stock < $quantity) {
            throw new BusinessException('Insufficient stock.');
        }

        $item->update(['quantity' => $quantity]);

        return $item->load('product');
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(User $user): void
    {
        $cart = $user->cart;
        $cart?->items()->delete();
    }
}
