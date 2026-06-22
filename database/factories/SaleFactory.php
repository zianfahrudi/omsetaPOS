<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 1000000);

        return [
            'store_id' => Store::factory(),
            'cashier_id' => User::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('########'),
            'status' => 'completed',
            'payment_method' => 'cash',
            'subtotal' => $subtotal,
            'grand_total' => $subtotal,
            'paid_amount' => $subtotal,
            'paid_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }
}
