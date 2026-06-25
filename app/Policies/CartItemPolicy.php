<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function modify(User $user, CartItem $item): bool
    {
        return $item->cart->user_id === $user->id;
    }
}
