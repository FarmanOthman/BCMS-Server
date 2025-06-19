<?php

namespace Database\Factories;

use App\Models\MonthlySalesReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

class MonthlySalesReportFactory extends Factory
{
    protected $model = MonthlySalesReport::class;

    public function definition()
    {
        $year = $this->faker->numberBetween(2024, 2025);
        $month = $this->faker->numberBetween(1, 12);
        
        // Create start and end dates based on year and month
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
          $totalSales = $this->faker->numberBetween(20, 100);
        $totalRevenue = $this->faker->randomFloat(2, 200000, 2000000);
        $totalProfit = $this->faker->randomFloat(2, 50000, 700000);
        $financeCost = $this->faker->randomFloat(2, 10000, 50000);
        $totalFinanceCost = $this->faker->randomFloat(2, $financeCost, $financeCost * 1.2); // Additional finance costs
        $netProfit = $totalProfit - $totalFinanceCost;
        
        // Calculate average daily profit (total profit divided by days in month)
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $avgDailyProfit = $totalProfit / $daysInMonth;
        
        // Generate a random day within the month for the best day
        $bestDay = \Carbon\Carbon::createFromDate($year, $month, $this->faker->numberBetween(1, $daysInMonth))->format('Y-m-d');
        $bestDayProfit = $this->faker->randomFloat(2, 5000, 100000);
          // Calculate profit margin as a percentage
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        
        $data = [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'avg_daily_profit' => $avgDailyProfit,
            'best_day' => $bestDay,
            'best_day_profit' => $bestDayProfit,
            'profit_margin' => $profitMargin,
            'finance_cost' => $financeCost,
            'net_profit' => $netProfit,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
        
        // Add total_finance_cost only if the column exists in the database
        if (Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
            $data['total_finance_cost'] = $totalFinanceCost;
        }
        
        return $data;
    }
    
    /**
     * Configure the model factory for a specific year and month.
     *
     * @param int $year The year
     * @param int $month The month (1-12)
     * @return $this
     */
    public function forYearMonth($year, $month)
    {
        return $this->state(function (array $attributes) use ($year, $month) {
            // Recalculate dates based on the new year and month
            $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
            
            // Generate a random day within the specified month for the best day
            $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $bestDay = \Carbon\Carbon::createFromDate($year, $month, $this->faker->numberBetween(1, $daysInMonth))->format('Y-m-d');
            
            return [
                'year' => $year,
                'month' => $month,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'best_day' => $bestDay,
            ];
        });
    }
}
