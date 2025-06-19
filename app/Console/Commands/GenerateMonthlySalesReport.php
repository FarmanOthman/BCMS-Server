<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\FinanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-monthly {year? : The year to generate the report for (YYYY)} {month? : The month to generate the report for (MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and store the monthly sales report from daily sales reports.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $yearInput = $this->argument('year');
            $monthInput = $this->argument('month');

            if ($yearInput && $monthInput) {
                $targetDate = Carbon::createFromDate($yearInput, $monthInput, 1);
            } else {
                $targetDate = Carbon::now()->subMonthNoOverflow()->startOfMonth(); // Previous month
            }

            $year = $targetDate->year;
            $month = $targetDate->month;

            $this->info("Generating monthly sales report for: {$year}-{$month}");

            $dailyReports = DailySalesReport::whereYear('report_date', $year)
                                            ->whereMonth('report_date', $month)
                                            ->get();

            if ($dailyReports->isEmpty()) {
                $this->info("No daily sales reports found for {$year}-{$month}. Creating an empty monthly report.");
                MonthlySalesReport::updateOrCreate(
                    ['year' => $year, 'month' => $month],
                    [
                        'start_date' => $targetDate->copy()->startOfMonth()->toDateString(),
                        'end_date' => $targetDate->copy()->endOfMonth()->toDateString(),
                        'total_sales' => 0,
                        'total_revenue' => 0,
                        'total_profit' => 0,
                        'avg_daily_profit' => 0,
                        'best_day' => null,
                        'best_day_profit' => 0,
                        'profit_margin' => 0,
                        'finance_cost' => 0,
                        'total_finance_cost' => 0,
                        'net_profit' => 0
                        // user ID fields will be auto-filled by Laravel's observer/middleware
                    ]
                );
                $this->info("Empty monthly sales report generated successfully for {$year}-{$month}");
                return 0;
            }

            $totalSales = $dailyReports->sum('total_sales');
            $totalRevenue = $dailyReports->sum('total_revenue');
            $totalProfit = $dailyReports->sum('total_profit');
            
            // Get finance records for the month to calculate finance costs
            $financeRecords = FinanceRecord::whereYear('record_date', $year)
                                          ->whereMonth('record_date', $month)
                                          ->get();
            $financeCost = $financeRecords->sum('cost');
            
            // Debug output for all daily reports
            $this->info("\nMonthly Report Calculation Details:");
            $this->info("  Daily Reports included in calculation:");
            foreach ($dailyReports as $dailyReport) {
                $this->info("    {$dailyReport->report_date}: Sales: {$dailyReport->total_sales}, Revenue: \${$dailyReport->total_revenue}, Profit: \${$dailyReport->total_profit}");
            }
            
            // Debug output for finance records
            $this->info("  Finance Records included in calculation:");
            foreach ($financeRecords as $record) {
                $this->info("    {$record->record_date}: {$record->type} - {$record->category}: \${$record->cost}");
            }
            $this->info("  Total Finance Cost: \${$financeCost}");
            
            // Calculate net profit (after finance costs)
            $netProfit = $totalProfit - $financeCost;
            
            $numberOfDaysWithReports = $dailyReports->count(); // Number of days that had reports (and potentially sales)
            $avgDailyProfit = $numberOfDaysWithReports > 0 ? $totalProfit / $numberOfDaysWithReports : 0;

            $bestDayReport = $dailyReports->sortByDesc('total_profit')->first();
            $bestDay = $bestDayReport ? $bestDayReport->report_date : null;
            $bestDayProfit = $bestDayReport ? $bestDayReport->total_profit : 0;

            // Profit margin = (Total Profit / Total Revenue) * 100
            // Avoid division by zero if total_revenue is 0
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
            
            $this->info("\nFinal Monthly Report Calculations:");
            $this->info("  Total Sales: {$totalSales}");
            $this->info("  Total Revenue: \${$totalRevenue}");
            $this->info("  Total Profit: \${$totalProfit}");
            $this->info("  Finance Cost: \${$financeCost}");
            $this->info("  Net Profit: \${$netProfit}");
            $this->info("  Profit Margin: {$profitMargin}%");
            
            DB::transaction(function () use ($year, $month, $targetDate, $totalSales, $totalRevenue, $totalProfit, 
                                            $avgDailyProfit, $bestDay, $bestDayProfit, $profitMargin, 
                                            $financeCost, $netProfit) {
                MonthlySalesReport::updateOrCreate(
                    ['year' => $year, 'month' => $month],
                    [
                        'start_date' => $targetDate->copy()->startOfMonth()->toDateString(),
                        'end_date' => $targetDate->copy()->endOfMonth()->toDateString(),
                        'total_sales' => $totalSales,
                        'total_revenue' => round((float)$totalRevenue, 2),
                        'total_profit' => round((float)$totalProfit, 2),
                        'avg_daily_profit' => round((float)$avgDailyProfit, 2),
                        'best_day' => $bestDay ? $bestDay->toDateString() : null, // Ensure it is a string date
                        'best_day_profit' => round((float)$bestDayProfit, 2),
                        'profit_margin' => round((float)$profitMargin, 2),
                        'finance_cost' => round((float)$financeCost, 2),
                        'total_finance_cost' => round((float)$financeCost, 2), // Set total_finance_cost equal to finance_cost
                        'net_profit' => round((float)$netProfit, 2)
                        // user ID fields will be auto-filled by Laravel's observer/middleware
                    ]
                );
            });

            $this->info("Monthly sales report generated successfully for {$year}-{$month}");
            $this->info("Summary:");
            $this->info("- Total Sales: {$totalSales}");
            $this->info("- Total Revenue: \$" . round((float)$totalRevenue, 2));
            $this->info("- Total Profit (before finance): \$" . round((float)$totalProfit, 2));
            $this->info("- Finance Cost: \$" . round((float)$financeCost, 2));
            $this->info("- Net Profit (after finance): \$" . round((float)$netProfit, 2));
            $this->info("- Profit Margin: " . round((float)$profitMargin, 2) . "%");
            return 0;

        } catch (\Exception $e) {
            Log::error("Error generating monthly sales report for {$year}-{$month}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            $this->error("An error occurred while generating monthly report for {$year}-{$month}: " . $e->getMessage());
            return 1;
        }
    }
}
