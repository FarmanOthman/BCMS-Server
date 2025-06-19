<?php

namespace Database\Factories;

use App\Models\DailySalesReport;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailySalesReportFactory extends Factory
{
    protected $model = DailySalesReport::class;

    public function definition()
    {
        $reportDate = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
        $totalSales = $this->faker->numberBetween(1, 20);
        $totalRevenue = $this->faker->randomFloat(2, 10000, 500000);
        $totalProfit = $this->faker->randomFloat(2, 5000, 200000);
        $avgProfitPerSale = $totalSales > 0 ? $totalProfit / $totalSales : 0;
        $highestSingleProfit = $this->faker->randomFloat(2, 1000, 50000);
        
        // Create a car for the most profitable car, or fetch one if available
        $carId = Car::inRandomOrder()->first()?->id ?? Car::factory()->create()->id;
        
        return [
            'report_date' => $reportDate,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'avg_profit_per_sale' => $avgProfitPerSale,
            'most_profitable_car_id' => $carId,
            'highest_single_profit' => $highestSingleProfit,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
    
    /**
     * Configure the model factory for a specific date.
     *
     * @param string $date Date in Y-m-d format
     * @return $this
     */
    public function forDate($date)
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'report_date' => $date,
            ];
        });
    }
}
