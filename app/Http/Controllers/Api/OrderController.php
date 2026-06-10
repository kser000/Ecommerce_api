<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Orders', description: 'Order management')]
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    #[OA\Get(
        path: '/api/orders',
        summary: "List current user's orders",
        security: [['sanctum' => []]],
        tags: ['Orders'],
        responses: [new OA\Response(response: 200, description: 'Order list')]
    )]
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    #[OA\Get(
        path: '/api/orders/{id}',
        summary: 'Get a single order',
        security: [['sanctum' => []]],
        tags: ['Orders'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Order details'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($order->load('items.product'));
    }

    #[OA\Post(
        path: '/api/orders',
        summary: 'Checkout: place order from cart',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'note', type: 'string', nullable: true)]
            )
        ),
        tags: ['Orders'],
        responses: [
            new OA\Response(response: 201, description: 'Order placed'),
            new OA\Response(response: 422, description: 'Cart empty or insufficient stock'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->orderService->checkout($request->user(), $request->note);

        return response()->json($order, 201);
    }

    #[OA\Patch(
        path: '/api/orders/{id}/status',
        summary: 'Update order status (admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['paid', 'shipped', 'delivered', 'cancelled']),
                ]
            )
        ),
        tags: ['Orders'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status updated'),
            new OA\Response(response: 422, description: 'Invalid status transition'),
        ]
    )]
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:paid,shipped,delivered,cancelled'],
        ]);

        $order = $this->orderService->updateStatus($order, $request->status);

        return response()->json($order);
    }
}
