<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
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
                        // 'created_by' and 'updated_by' could be set to a system user ID
                    ]
                );
                $this->info("Empty monthly sales report generated successfully for {$year}-{$month}");
                return 0;
            }

            $totalSales = $dailyReports->sum('total_sales');
            $totalRevenue = $dailyReports->sum('total_revenue');
            $totalProfit = $dailyReports->sum('total_profit');
            
            $numberOfDaysWithReports = $dailyReports->count(); // Number of days that had reports (and potentially sales)
            $avgDailyProfit = $numberOfDaysWithReports > 0 ? $totalProfit / $numberOfDaysWithReports : 0;

            $bestDayReport = $dailyReports->sortByDesc('total_profit')->first();
            $bestDay = $bestDayReport ? $bestDayReport->report_date : null;
            $bestDayProfit = $bestDayReport ? $bestDayReport->total_profit : 0;

            // Profit margin = (Total Profit / Total Revenue) * 100
            // Avoid division by zero if total_revenue is 0
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            DB::transaction(function () use ($year, $month, $targetDate, $totalSales, $totalRevenue, $totalProfit, $avgDailyProfit, $bestDay, $bestDayProfit, $profitMargin) {
                MonthlySalesReport::updateOrCreate(
                    ['year' => $year, 'month' => $month],
                    [
                        'start_date' => $targetDate->copy()->startOfMonth()->toDateString(),
                        'end_date' => $targetDate->copy()->endOfMonth()->toDateString(),
                        'total_sales' => $totalSales,
                        'total_revenue' => $totalRevenue,
                        'total_profit' => $totalProfit,
                        'avg_daily_profit' => $avgDailyProfit,
                        'best_day' => $bestDay ? $bestDay->toDateString() : null, // Ensure it is a string date
                        'best_day_profit' => $bestDayProfit,
                        'profit_margin' => $profitMargin,
                        // 'created_by' and 'updated_by'
                    ]
                );
            });

            $this->info("Monthly sales report generated successfully for {$year}-{$month}");
            return 0;

        } catch (\Exception $e) {
            Log::error("Error generating monthly sales report for {$year}-{$month}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            $this->error("An error occurred while generating monthly report for {$year}-{$month}: " . $e->getMessage());
            return 1;
        }
    }
}
