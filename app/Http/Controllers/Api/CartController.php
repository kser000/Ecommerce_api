<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Cart', description: 'Shopping cart management')]
class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    #[OA\Get(
        path: '/api/cart',
        summary: "Get current user's cart",
        security: [['sanctum' => []]],
        tags: ['Cart'],
        responses: [new OA\Response(response: 200, description: 'Cart with items')]
    )]
    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $cart->load('items.product');

        return response()->json($cart);
    }

    #[OA\Post(
        path: '/api/cart/items',
        summary: 'Add item to cart',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                ]
            )
        ),
        tags: ['Cart'],
        responses: [
            new OA\Response(response: 201, description: 'Item added'),
            new OA\Response(response: 422, description: 'Insufficient stock or invalid product'),
        ]
    )]
    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $item = $this->cartService->addItem($request->user(), $product, $data['quantity']);

        return response()->json($item, 201);
    }

    #[OA\Put(
        path: '/api/cart/items/{id}',
        summary: 'Update cart item quantity',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [new OA\Property(property: 'quantity', type: 'integer', minimum: 1)]
            )
        ),
        tags: ['Cart'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Item updated')]
    )]
    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = $this->cartService->updateItem($request->user(), $cartItem, $data['quantity']);

        return response()->json($item);
    }

    #[OA\Delete(
        path: '/api/cart/items/{id}',
        summary: 'Remove item from cart',
        security: [['sanctum' => []]],
        tags: ['Cart'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 204, description: 'Item removed')]
    )]
    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->cartService->removeItem($request->user(), $cartItem);

        return response()->json(null, 204);
    }

    #[OA\Delete(
        path: '/api/cart',
        summary: 'Clear entire cart',
        security: [['sanctum' => []]],
        tags: ['Cart'],
        responses: [new OA\Response(response: 204, description: 'Cart cleared')]
    )]
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());

        return response()->json(null, 204);
    }
}
