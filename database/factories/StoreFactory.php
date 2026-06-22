<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'owner_id' => User::factory(),
            'name' => fake()->company().' Store',
            'code' => 'ST-'.fake()->unique()->numerify('#####'),
            'is_active' => true,
        ];
    }
}
