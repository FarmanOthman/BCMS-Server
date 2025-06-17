<?php

namespace Database\Factories;

use App\Models\Buyer;
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
            'phone' => substr(preg_replace('/[^0-9]/', '', $this->faker->e164PhoneNumber()), 0, 20),
            'address' => $this->faker->address,
            // 'created_by' => User::factory(), // Uncomment if needed
            // 'updated_by' => User::factory(), // Uncomment if needed
        ];
    }
}
