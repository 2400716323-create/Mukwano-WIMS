<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-########'),
            'sap_material_number' => fake()->unique()->numerify('##########'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'category_id' => Category::factory(),
            'unit' => 'each',
            'min_level' => 1,
            'reorder_level' => 2,
            'max_level' => 5,
            'status' => 'active',
        ];
    }
}
