<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Make;
use App\Models\Model as CarModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'make_id' => Make::factory(),
            'model_id' => CarModel::factory(),
            'year' => $this->faker->numberBetween(2000, 2025),
            'vin' => Str::random(17),
            'cost_price' => $this->faker->randomFloat(2, 5000, 40000),
            'transition_cost' => $this->faker->randomFloat(2, 100, 1000),
            'total_repair_cost' => $this->faker->randomFloat(2, 0, 5000),
            'selling_price' => $this->faker->randomFloat(2, 7000, 50000), // This might be an asking price, actual sale price in Sale record
            'public_price' => $this->faker->randomFloat(2, 7500, 55000),
            'status' => $this->faker->randomElement(['available', 'sold', 'pending']),
            'sold_at' => null,
            // 'created_by' => User::factory(), // Uncomment if you have User factory and want to associate
            // 'updated_by' => User::factory(), // Uncomment if you have User factory and want to associate
            // 'sold_by' => null, // User::factory() // Uncomment if you have User factory and want to associate
        ];
    }
}
