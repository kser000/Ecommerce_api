<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        User::factory()->create([
            'name'     => 'Test User',
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);

        $categories = [
            ['name' => '電子產品', 'slug' => 'electronics'],
            ['name' => '服裝', 'slug' => 'clothing'],
            ['name' => '書籍', 'slug' => 'books'],
            ['name' => '家居', 'slug' => 'home'],
            ['name' => '運動', 'slug' => 'sports'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        Product::factory(50)->create();
    }
}
