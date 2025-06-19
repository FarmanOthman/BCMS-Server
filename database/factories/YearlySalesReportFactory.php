<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\YearlySalesReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class YearlySalesReportFactory extends Factory
{
    protected $model = YearlySalesReport::class;    public function definition()
    {
        $totalSales = $this->faker->numberBetween(50, 500);
        $totalRevenue = $this->faker->numberBetween(1000000, 10000000);
        $totalProfit = $this->faker->numberBetween(300000, 3000000);
        
        // Calculate a realistic average monthly profit
        $avgMonthlyProfit = $totalProfit / 12;
        
        // Create a best month (1-12)
        $bestMonth = $this->faker->numberBetween(1, 12);
        $bestMonthProfit = $this->faker->numberBetween($avgMonthlyProfit, $avgMonthlyProfit * 2);
        
        // Calculate profit margin
        $profitMargin = ($totalProfit / $totalRevenue) * 100;
        
        return [
            'year' => $this->faker->numberBetween(2020, 2025),
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'avg_monthly_profit' => $avgMonthlyProfit,
            'best_month' => $bestMonth,
            'best_month_profit' => $bestMonthProfit,
            'profit_margin' => $profitMargin,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
