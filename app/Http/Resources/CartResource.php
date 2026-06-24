<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');

        return [
            'id'    => $this->id,
            'items' => CartItemResource::collection($items),
            'total' => $items instanceof \Illuminate\Support\Collection
                ? round($items->sum(fn ($i) => $i->product->price * $i->quantity), 2)
                : 0,
        ];
    }
}
