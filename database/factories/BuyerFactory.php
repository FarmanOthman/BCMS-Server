<?php

namespace Database\Factories;

use App\Models\Buyer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BuyerFactory extends Factory
{
    protected $model = Buyer::class;

    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            // 'created_by' => User::factory(), // Uncomment if needed
            // 'updated_by' => User::factory(), // Uncomment if needed
        ];
    }
}
