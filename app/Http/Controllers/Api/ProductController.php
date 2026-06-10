<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Product management')]
class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/products',
        summary: 'List products with search and pagination',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated product list')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')->active();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($products);
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get a single product',
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product details'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load('category'));
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product (admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'stock'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'stock', type: 'integer'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(response: 201, description: 'Product created'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json($product->load('category'), 201);
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update a product (admin only)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'name', type: 'string')]
            )
        ),
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Product updated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $product->update($data);

        return response()->json($product->load('category'));
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Delete a product (admin only)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
