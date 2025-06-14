<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateYearlySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-yearly {year? : The year to generate the report for (YYYY). Defaults to the previous year.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and store the yearly sales report from monthly sales reports.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $yearInput = $this->argument('year');
            $targetYear = $yearInput ? (int)$yearInput : Carbon::now()->subYear()->year;

            $this->info("Generating yearly sales report for: {$targetYear}");

            $monthlyReports = MonthlySalesReport::where('year', $targetYear)->get();

            if ($monthlyReports->isEmpty()) {
                $this->info("No monthly sales reports found for {$targetYear}. Creating an empty yearly report.");
                YearlySalesReport::updateOrCreate(
                    ['year' => $targetYear],
                    [
                        'total_sales' => 0,
                        'total_revenue' => 0,
                        'total_profit' => 0,
                        'avg_monthly_profit' => 0,
                        'best_month' => null,
                        'best_month_profit' => 0,
                        'profit_margin' => 0,
                        'yoy_growth' => null, // Year-over-Year growth, null if no previous year data
                        // 'created_by' and 'updated_by' could be set to a system user ID
                    ]
                );
                $this->info("Empty yearly sales report generated successfully for {$targetYear}");
                return 0;
            }

            $totalSales = $monthlyReports->sum('total_sales');
            $totalRevenue = $monthlyReports->sum('total_revenue');
            $totalProfit = $monthlyReports->sum('total_profit');
            
            $numberOfMonthsWithReports = $monthlyReports->count();
            $avgMonthlyProfit = $numberOfMonthsWithReports > 0 ? $totalProfit / $numberOfMonthsWithReports : 0;

            $bestMonthReport = $monthlyReports->sortByDesc('total_profit')->first();
            $bestMonth = $bestMonthReport ? $bestMonthReport->month : null;
            $bestMonthProfit = $bestMonthReport ? $bestMonthReport->total_profit : 0;

            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

            // Calculate Year-over-Year (YoY) growth if possible
            $yoyGrowth = null;
            $previousYearReport = YearlySalesReport::find($targetYear - 1);
            if ($previousYearReport && $previousYearReport->total_profit > 0) {
                $yoyGrowth = (($totalProfit - $previousYearReport->total_profit) / $previousYearReport->total_profit) * 100;
            } elseif ($previousYearReport && $previousYearReport->total_profit == 0 && $totalProfit > 0) {
                $yoyGrowth = 100.0; // Or some indicator of growth from zero
            }

            DB::transaction(function () use ($targetYear, $totalSales, $totalRevenue, $totalProfit, $avgMonthlyProfit, $bestMonth, $bestMonthProfit, $profitMargin, $yoyGrowth) {
                YearlySalesReport::updateOrCreate(
                    ['year' => $targetYear],
                    [
                        'total_sales' => $totalSales,
                        'total_revenue' => $totalRevenue,
                        'total_profit' => $totalProfit,
                        'avg_monthly_profit' => $avgMonthlyProfit,
                        'best_month' => $bestMonth,
                        'best_month_profit' => $bestMonthProfit,
                        'profit_margin' => $profitMargin,
                        'yoy_growth' => $yoyGrowth,
                        // 'created_by' and 'updated_by'
                    ]
                );
            });

            $this->info("Yearly sales report generated successfully for {$targetYear}");
            return 0;

        } catch (\Exception $e) {
            Log::error("Error generating yearly sales report for {$targetYear}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            $this->error("An error occurred while generating yearly report for {$targetYear}: " . $e->getMessage());
            return 1;
        }
    }
}
