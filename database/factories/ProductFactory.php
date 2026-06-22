<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->numberBetween(1000, 50000);

        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'cost_price' => $cost,
            'sell_price' => $cost + fake()->numberBetween(1000, 50000),
            'stock' => fake()->numberBetween(0, 500),
            'product_type' => 'goods',
            'is_active' => true,
        ];
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'service',
        ]);
    }

    public function goods(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'goods',
        ]);
    }
}
