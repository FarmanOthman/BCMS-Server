<?php

namespace App\Observers;

use App\Models\DailySalesReport;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Log;

class DailySalesReportObserver
{
    protected $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Handle the DailySalesReport "created" event.
     */
    public function created(DailySalesReport $dailyReport): void
    {
        try {
            $reportDate = $dailyReport->report_date;
            $year = $reportDate->year;
            $month = $reportDate->month;
            
            Log::info("Daily sales report created for {$reportDate}, triggering monthly report update for {$year}-{$month}");
            
            // Update monthly report for this month (creates if doesn't exist)
            $this->reportService->generateMonthlyReport($year, $month);
            
            Log::info("Successfully updated monthly report for {$year}-{$month} after daily report creation");
        } catch (\Exception $e) {
            Log::error("Failed to update monthly report after daily report creation: " . $e->getMessage());
            // Don't throw the exception to prevent the daily report creation from failing
        }
    }

    /**
     * Handle the DailySalesReport "updated" event.
     */
    public function updated(DailySalesReport $dailyReport): void
    {
        try {
            $reportDate = $dailyReport->report_date;
            $year = $reportDate->year;
            $month = $reportDate->month;
            
            Log::info("Daily sales report updated for {$reportDate}, triggering monthly report update for {$year}-{$month}");
            
            // Update monthly report for this month (creates if doesn't exist)
            $this->reportService->generateMonthlyReport($year, $month);
            
            Log::info("Successfully updated monthly report for {$year}-{$month} after daily report update");
        } catch (\Exception $e) {
            Log::error("Failed to update monthly report after daily report update: " . $e->getMessage());
        }
    }

    /**
     * Handle the DailySalesReport "deleted" event.
     */
    public function deleted(DailySalesReport $dailyReport): void
    {
        try {
            $reportDate = $dailyReport->report_date;
            $year = $reportDate->year;
            $month = $reportDate->month;
            
            Log::info("Daily sales report deleted for {$reportDate}, triggering monthly report update for {$year}-{$month}");
            
            // Update monthly report for this month (creates if doesn't exist)
            $this->reportService->generateMonthlyReport($year, $month);
            
            Log::info("Successfully updated monthly report for {$year}-{$month} after daily report deletion");
        } catch (\Exception $e) {
            Log::error("Failed to update monthly report after daily report deletion: " . $e->getMessage());
        }
    }
} 