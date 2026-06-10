<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $admin;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin    = User::factory()->create(['role' => 'admin']);

        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $this->product = Product::factory()->create([
            'price'       => 100.00,
            'stock'       => 10,
            'category_id' => $category->id,
        ]);
    }

    public function test_guest_cannot_place_order(): void
    {
        $this->postJson('/api/orders')->assertStatus(401);
    }

    public function test_cannot_checkout_with_empty_cart(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/orders')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cart is empty.');
    }

    public function test_customer_can_checkout_and_stock_is_decremented(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 3,
            ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/orders')
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'status', 'total_amount', 'items']);

        $this->assertEquals('pending', $response->json('status'));
        $this->assertEquals('300.00', $response->json('total_amount'));

        $this->assertEquals(7, $this->product->fresh()->stock);
    }

    public function test_cart_is_cleared_after_checkout(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
            ]);

        $this->actingAs($this->customer)->postJson('/api/orders');

        $this->actingAs($this->customer)
            ->getJson('/api/cart')
            ->assertJsonPath('items', []);
    }

    public function test_cannot_checkout_when_stock_insufficient(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
            ]);

        $this->product->update(['stock' => 0]);

        $this->actingAs($this->customer)
            ->postJson('/api/orders')
            ->assertStatus(422);
    }

    public function test_customer_can_view_own_orders(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->actingAs($this->customer)->postJson('/api/orders');

        $this->actingAs($this->customer)
            ->getJson('/api/orders')
            ->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    public function test_customer_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherUser)
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);

        $orderResponse = $this->actingAs($otherUser)->postJson('/api/orders');
        $orderId = $orderResponse->json('id');

        $this->actingAs($this->customer)
            ->getJson("/api/orders/{$orderId}")
            ->assertStatus(403);
    }

    public function test_admin_can_update_order_status(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);

        $orderResponse = $this->actingAs($this->customer)->postJson('/api/orders');
        $orderId = $orderResponse->json('id');

        $this->actingAs($this->admin)
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'paid'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'paid');
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/cart/items', ['product_id' => $this->product->id, 'quantity' => 1]);

        $orderResponse = $this->actingAs($this->customer)->postJson('/api/orders');
        $orderId = $orderResponse->json('id');

        $this->actingAs($this->admin)
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'delivered'])
            ->assertStatus(422);
    }
}
