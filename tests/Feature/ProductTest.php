<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_products(): void
    {
        Product::factory()->count(5)->create();

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'total', 'per_page']);
    }

    public function test_products_can_be_searched(): void
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

        Product::factory()->create([
            'name'        => 'Wireless Headphones',
            'category_id' => $category->id,
        ]);
        Product::factory()->create(['name' => 'Running Shoes']);

        $this->getJson('/api/products?search=headphones')
            ->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    public function test_anyone_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJson(['id' => $product->id]);
    }

    public function test_guest_cannot_create_product(): void
    {
        $this->postJson('/api/products', ['name' => 'Test', 'price' => 100, 'stock' => 10])
            ->assertStatus(401);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->postJson('/api/products', ['name' => 'Test', 'price' => 100, 'stock' => 10])
            ->assertStatus(403);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Books', 'slug' => 'books']);

        $this->actingAs($admin)
            ->postJson('/api/products', [
                'name'        => 'Laravel in Action',
                'price'       => 299.00,
                'stock'       => 50,
                'category_id' => $category->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('name', 'Laravel in Action');

        $this->assertDatabaseHas('products', ['name' => 'Laravel in Action']);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['price' => 100]);

        $this->actingAs($admin)
            ->putJson("/api/products/{$product->id}", ['price' => 200])
            ->assertStatus(200)
            ->assertJsonPath('price', '200.00');
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
