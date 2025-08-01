<?php

namespace App\Services;

use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Models\Sale;
use App\Models\ReportGenerationTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportGenerationService
{
    /**
     * Generate or update daily sales report for a specific date
     */
    public function generateDailyReport(string $date): DailySalesReport
    {
        $dateObj = Carbon::parse($date);
        
        // Get all sales for the specific date
        $sales = Sale::whereDate('sale_date', $date)->get();
        
        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('sale_price');
        $totalProfit = $sales->sum('profit_loss');
        $avgSalePrice = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
        $avgProfit = $totalSales > 0 ? $totalProfit / $totalSales : 0;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        
        // Create or update daily report
        $report = DailySalesReport::updateOrCreate(
            ['report_date' => $date],
            [
                'total_sales' => $totalSales,
                'total_revenue' => round((float)$totalRevenue, 2),
                'total_profit' => round((float)$totalProfit, 2),
                'avg_profit_per_sale' => round((float)$avgProfit, 2),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );
        
        Log::info("Generated daily sales report for {$date}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit");
        
        return $report;
    }
    
    /**
     * Generate or update monthly sales report for a specific year/month
     */
    public function generateMonthlyReport(int $year, int $month): MonthlySalesReport
    {
        // Get all daily reports for the month
        $dailyReports = DailySalesReport::whereYear('report_date', $year)
            ->whereMonth('report_date', $month)
            ->get();
        
        if ($dailyReports->isEmpty()) {
            // If no daily reports exist, calculate from sales directly
            $sales = Sale::whereYear('sale_date', $year)
                ->whereMonth('sale_date', $month)
                ->get();
            
            $totalSales = $sales->count();
            $totalRevenue = $sales->sum('sale_price');
            $totalProfit = $sales->sum('profit_loss');
            $avgSalePrice = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            $avgProfit = $totalSales > 0 ? $totalProfit / $totalSales : 0;
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
            $daysWithSales = $sales->groupBy(function($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-m-d');
            })->count();
        } else {
            // Calculate from daily reports
            $totalSales = $dailyReports->sum('total_sales');
            $totalRevenue = $dailyReports->sum('total_revenue');
            $totalProfit = $dailyReports->sum('total_profit');
            $avgSalePrice = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
            $avgProfit = $totalSales > 0 ? $totalProfit / $totalSales : 0;
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
            $daysWithSales = $dailyReports->count();
        }
        
        // Calculate finance costs for the month
        $financeRecords = \App\Models\FinanceRecord::whereYear('record_date', $year)
            ->whereMonth('record_date', $month)
            ->get();
        
        $totalFinanceCost = $financeRecords->where('type', 'expense')->sum('cost');
        $totalFinanceIncome = $financeRecords->where('type', 'income')->sum('cost');
        $netFinanceCost = $totalFinanceCost - $totalFinanceIncome;
        
        // Calculate net profit (sales profit - finance costs)
        $netProfit = $totalProfit - $netFinanceCost;
        
        // Create or update monthly report
        $startDate = Carbon::create($year, $month, 1)->format('Y-m-d');
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        
        $report = MonthlySalesReport::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_sales' => $totalSales,
                'total_revenue' => round((float)$totalRevenue, 2),
                'total_profit' => round((float)$totalProfit, 2),
                'avg_daily_profit' => round((float)$avgProfit, 2),
                'profit_margin' => round((float)$profitMargin, 2),
                'finance_cost' => round((float)$totalFinanceCost, 2),
                'total_finance_cost' => round((float)$netFinanceCost, 2),
                'net_profit' => round((float)$netProfit, 2),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );
        
        Log::info("Generated monthly sales report for {$year}-{$month}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit, \${$netFinanceCost} finance cost, \${$netProfit} net profit");
        
        return $report;
    }
    
    /**
     * Generate or update yearly sales report for a specific year
     */
    public function generateYearlyReport(int $year): YearlySalesReport
    {
        // Get all monthly reports for the year
        $monthlyReports = MonthlySalesReport::where('year', $year)->get();
        
        if ($monthlyReports->isEmpty()) {
            // If no monthly reports exist, calculate from sales directly
            $sales = Sale::whereYear('sale_date', $year)->get();
            
            $totalSales = $sales->count();
            $totalRevenue = $sales->sum('sale_price');
            $totalProfit = $sales->sum('profit_loss');
            $avgMonthlyProfit = 0;
            $bestMonth = null;
            $bestMonthProfit = 0;
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        } else {
            // Calculate from monthly reports
            $totalSales = $monthlyReports->sum('total_sales');
            $totalRevenue = $monthlyReports->sum('total_revenue');
            $totalProfit = $monthlyReports->sum('total_profit');
            $avgMonthlyProfit = $monthlyReports->count() > 0 ? $totalProfit / $monthlyReports->count() : 0;
            
            $bestMonthReport = $monthlyReports->sortByDesc('total_profit')->first();
            $bestMonth = $bestMonthReport ? $bestMonthReport->month : null;
            $bestMonthProfit = $bestMonthReport ? $bestMonthReport->total_profit : 0;
            
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        }
        
        // Calculate total finance costs for the year
        if ($monthlyReports->isEmpty()) {
            // If no monthly reports exist, calculate from finance records directly
            $financeRecords = \App\Models\FinanceRecord::whereYear('record_date', $year)->get();
            $totalFinanceCost = $financeRecords->where('type', 'expense')->sum('cost');
            $totalFinanceIncome = $financeRecords->where('type', 'income')->sum('cost');
            $netFinanceCost = $totalFinanceCost - $totalFinanceIncome;
        } else {
            // Calculate from monthly reports (for consistency)
            $netFinanceCost = $monthlyReports->sum('total_finance_cost');
        }
        
        // Calculate net profit (sales profit - finance costs)
        $netProfit = $totalProfit - $netFinanceCost;
        
        // Create or update yearly report
        $report = YearlySalesReport::updateOrCreate(
            ['year' => $year],
            [
                'total_sales' => $totalSales,
                'total_revenue' => round((float)$totalRevenue, 2),
                'total_profit' => round((float)$totalProfit, 2),
                'avg_monthly_profit' => round((float)$avgMonthlyProfit, 2),
                'best_month' => $bestMonth,
                'best_month_profit' => round((float)$bestMonthProfit, 2),
                'profit_margin' => round((float)$profitMargin, 2),
                'total_finance_cost' => round((float)$netFinanceCost, 2),
                'total_net_profit' => round((float)$netProfit, 2),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );
        
        Log::info("Generated yearly sales report for {$year}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit, \${$netFinanceCost} finance cost, \${$netProfit} net profit");
        
        return $report;
    }
    
    /**
     * Generate all reports for a specific sale date (optimized version)
     */
    public function generateReportsForSale(string $saleDate): void
    {
        $dateObj = Carbon::parse($saleDate);
        $year = $dateObj->year;
        $month = $dateObj->month;
        $date = $dateObj->format('Y-m-d');
        
        try {
            DB::transaction(function () use ($date, $year, $month) {
                $tracker = ReportGenerationTracker::getInstance();
                
                // Always generate/update daily report when a new sale is created
                Log::info("Generating/updating daily report for date: {$date}");
                $this->generateDailyReport($date);
                
                // Update tracker only if this is a new date
                if ($tracker->needsDailyReport($date)) {
                    $tracker->updateLastDailyReportDate($date);
                }
                
                // Always generate/update monthly report when a new sale is created
                Log::info("Generating/updating monthly report for year: {$year}, month: {$month}");
                $this->generateMonthlyReport($year, $month);
                
                // Update tracker only if this is a new month
                if ($tracker->needsMonthlyReport($year, $month)) {
                    $tracker->updateLastMonthlyReportDate($year, $month);
                }
                
                // Always generate/update yearly report when a new sale is created
                Log::info("Generating/updating yearly report for year: {$year}");
                $this->generateYearlyReport($year);
                
                // Update tracker only if this is a new year
                if ($tracker->needsYearlyReport($year)) {
                    $tracker->updateLastYearlyReportDate($year);
                }
            });
            
            Log::info("Successfully processed reports for sale date: {$saleDate}");
        } catch (\Exception $e) {
            Log::error("Failed to generate reports for sale date {$saleDate}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if reports exist for a specific date
     */
    public function checkReportsExist(string $saleDate): array
    {
        $dateObj = Carbon::parse($saleDate);
        $year = $dateObj->year;
        $month = $dateObj->month;
        $date = $dateObj->format('Y-m-d');
        
        $dailyExists = DailySalesReport::where('report_date', $date)->exists();
        $monthlyExists = MonthlySalesReport::where('year', $year)->where('month', $month)->exists();
        $yearlyExists = YearlySalesReport::where('year', $year)->exists();
        
        return [
            'daily' => $dailyExists,
            'monthly' => $monthlyExists,
            'yearly' => $yearlyExists,
            'all_exist' => $dailyExists && $monthlyExists && $yearlyExists
        ];
    }

    /**
     * Get missing reports for a date range
     */
    public function getMissingReports(string $fromDate, string $toDate): array
    {
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);
        
        $missingReports = [];
        
        // Get all unique sale dates in the range
        $saleDates = Sale::whereBetween('sale_date', [$from, $to])
            ->distinct()
            ->pluck('sale_date')
            ->sort()
            ->values();
        
        foreach ($saleDates as $saleDate) {
            $reportStatus = $this->checkReportsExist($saleDate);
            if (!$reportStatus['all_exist']) {
                $missingReports[] = [
                    'date' => $saleDate,
                    'status' => $reportStatus
                ];
            }
        }
        
        return $missingReports;
    }

    /**
     * Force generate all reports for a specific sale date (ignores tracker)
     */
    public function forceGenerateReportsForSale(string $saleDate): void
    {
        $dateObj = Carbon::parse($saleDate);
        $year = $dateObj->year;
        $month = $dateObj->month;
        $date = $dateObj->format('Y-m-d');
        
        try {
            DB::transaction(function () use ($date, $year, $month) {
                // Generate daily report
                $this->generateDailyReport($date);
                
                // Generate monthly report
                $this->generateMonthlyReport($year, $month);
                
                // Generate yearly report
                $this->generateYearlyReport($year);
            });
            
            Log::info("Successfully force generated all reports for sale date: {$saleDate}");
        } catch (\Exception $e) {
            Log::error("Failed to force generate reports for sale date {$saleDate}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize the tracker with existing report data
     */
    public function initializeTracker(): void
    {
        try {
            $tracker = ReportGenerationTracker::getInstance();
            
            // Find the latest daily report date
            $latestDaily = DailySalesReport::orderBy('report_date', 'desc')->first();
            if ($latestDaily) {
                $tracker->updateLastDailyReportDate($latestDaily->report_date);
                Log::info("Initialized tracker with latest daily report date: {$latestDaily->report_date}");
            }
            
            // Find the latest monthly report
            $latestMonthly = MonthlySalesReport::orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();
            if ($latestMonthly) {
                $tracker->updateLastMonthlyReportDate($latestMonthly->year, $latestMonthly->month);
                Log::info("Initialized tracker with latest monthly report: {$latestMonthly->year}-{$latestMonthly->month}");
            }
            
            // Find the latest yearly report
            $latestYearly = YearlySalesReport::orderBy('year', 'desc')->first();
            if ($latestYearly) {
                $tracker->updateLastYearlyReportDate($latestYearly->year);
                Log::info("Initialized tracker with latest yearly report: {$latestYearly->year}");
            }
            
            Log::info("Successfully initialized report generation tracker");
        } catch (\Exception $e) {
            Log::error("Failed to initialize tracker: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Regenerate reports for a specific month when finance records change
     */
    public function regenerateReportsForMonth(int $year, int $month): void
    {
        try {
            DB::transaction(function () use ($year, $month) {
                Log::info("Regenerating reports for {$year}-{$month} due to finance record changes");
                
                // Regenerate monthly report
                $this->generateMonthlyReport($year, $month);
                
                // Regenerate yearly report
                $this->generateYearlyReport($year);
            });
            
            Log::info("Successfully regenerated reports for {$year}-{$month}");
        } catch (\Exception $e) {
            Log::error("Failed to regenerate reports for {$year}-{$month}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Auto-generate reports for new month
     */
    public function autoGenerateReportsForNewMonth(): void
    {
        $currentDate = Carbon::now();
        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;
        
        // Check if monthly report exists for current month
        $existingReport = MonthlySalesReport::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->first();
        
        if (!$existingReport) {
            Log::info("Auto-generating reports for new month: {$currentYear}-{$currentMonth}");
            $this->generateMonthlyReport($currentYear, $currentMonth);
            $this->generateYearlyReport($currentYear);
        }
    }
} 