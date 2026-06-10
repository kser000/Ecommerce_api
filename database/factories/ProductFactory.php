<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::inRandomOrder()->value('id'),
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => fake()->paragraph(),
            'price'       => fake()->randomFloat(2, 10, 5000),
            'stock'       => fake()->numberBetween(0, 200),
            'is_active'   => true,
        ];
    }
}
