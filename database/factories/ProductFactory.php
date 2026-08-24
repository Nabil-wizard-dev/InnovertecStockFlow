<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'product_reference' => 'REF-'.Str::upper(Str::random(8)),
            'unit_price' => fake()->randomFloat(2, 1, 500),
            'available_quantity' => fake()->numberBetween(1, 200),
            'status' => ProductStatus::Available,
        ];
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_quantity' => 0,
            'status' => ProductStatus::OutOfStock,
        ]);
    }
}
