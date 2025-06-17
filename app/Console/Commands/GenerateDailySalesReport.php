<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\DailySalesReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-daily {date? : The date to generate the report for (YYYY-MM-DD). Defaults to yesterday.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and store the daily sales report.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $dateInput = $this->argument('date');
            $targetDate = $dateInput ? Carbon::parse($dateInput) : Carbon::yesterday();
            $this->info("Generating daily sales report for: " . $targetDate->toDateString());

            $salesForDate = Sale::whereDate('sale_date', $targetDate)->get();

            if ($salesForDate->isEmpty()) {
                $this->info('No sales found for ' . $targetDate->toDateString() . ". Creating an empty report.");
                DailySalesReport::updateOrCreate(
                    ['report_date' => $targetDate->toDateString()],
                    [
                        'total_sales' => 0,
                        'total_revenue' => 0.00,
                        'total_profit' => 0.00,
                        'avg_profit_per_sale' => 0.00,
                        'most_profitable_car_id' => null,
                        'highest_single_profit' => 0.00,
                        // 'created_by' and 'updated_by' could be set to a system user ID if available
                    ]
                );
                $this->info('Empty daily sales report generated successfully for ' . $targetDate->toDateString());
                return 0;
            }

            $totalSales = $salesForDate->count();
            $totalRevenue = $salesForDate->sum('sale_price');
            $totalProfit = $salesForDate->sum('profit_loss');
            $avgProfitPerSale = $totalSales > 0 ? $totalProfit / $totalSales : 0;

            // Debug information
            $this->info("Calculation Details:");
            foreach ($salesForDate as $sale) {
                $car = $sale->car;
                $this->info("- Sale: {$car->year} {$car->make->name} {$car->model->name}");
                $this->info("  Sale Price: \${$sale->sale_price}, Cost: \${$sale->purchase_cost}, Profit: \${$sale->profit_loss}");
            }
            
            $this->info("Summary:");
            $this->info("- Total Sales: {$totalSales}");
            $this->info("- Total Revenue: \$" . round((float)$totalRevenue, 2));
            $this->info("- Total Profit: \$" . round((float)$totalProfit, 2));
            $this->info("- Average Profit per Sale: \$" . round((float)$avgProfitPerSale, 2));

            $mostProfitableSale = $salesForDate->sortByDesc('profit_loss')->first();
            $mostProfitableCarId = $mostProfitableSale ? $mostProfitableSale->car_id : null;
            $highestSingleProfit = $mostProfitableSale ? $mostProfitableSale->profit_loss : 0;

            // Use a transaction to ensure atomicity if multiple operations were involved,
            // though updateOrCreate is generally atomic for its own operation.
            DB::transaction(function () use ($targetDate, $totalSales, $totalRevenue, $totalProfit, $avgProfitPerSale, $mostProfitableCarId, $highestSingleProfit) {
                DailySalesReport::updateOrCreate(
                    ['report_date' => $targetDate->toDateString()],
                    [
                        'total_sales' => $totalSales,
                        'total_revenue' => round((float)$totalRevenue, 2),
                        'total_profit' => round((float)$totalProfit, 2),
                        'avg_profit_per_sale' => round((float)$avgProfitPerSale, 2),
                        'most_profitable_car_id' => $mostProfitableCarId,
                        'highest_single_profit' => round((float)$highestSingleProfit, 2),
                        // Consider setting 'created_by' and 'updated_by' if you have a system user
                        // For example: 'created_by' => User::where('role', 'System')->first()->id,
                    ]
                );
            });

            $this->info('Daily sales report generated successfully for ' . $targetDate->toDateString());
            return 0;

        } catch (\Exception $e) {
            Log::error('Error generating daily sales report: ' . $e->getMessage());
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }
}
