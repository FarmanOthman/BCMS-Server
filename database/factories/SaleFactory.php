<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Car;
use App\Models\Buyer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        $car = Car::factory()->create(); // Ensure car is created first
        
        // Calculate purchase_cost based on car's costs
        $purchaseCost = ($car->cost_price ?? 0) + 
                        ($car->transition_cost ?? 0) + 
                        ($car->total_repair_cost ?? 0);

        // Sale price should be higher than total cost for profit
        $salePrice = $purchaseCost + $this->faker->randomFloat(2, 500, 10000);

        return [
            'id' => Str::uuid(),
            'car_id' => $car->id,
            'buyer_id' => Buyer::factory(),
            'sale_price' => $salePrice,
            'purchase_cost' => $purchaseCost, // This is the total cost for the business
            'profit_loss' => $salePrice - $purchaseCost,
            'sale_date' => $this->faker->dateTimeThisYear(),
            'notes' => $this->faker->sentence,
            // 'created_by' => User::factory(), // Uncomment if needed
            // 'updated_by' => User::factory(), // Uncomment if needed
        ];
    }
}
