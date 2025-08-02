<?php

namespace App\Observers;

use App\Models\MonthlySalesReport;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Log;

class MonthlySalesReportObserver
{
    protected $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Handle the MonthlySalesReport "created" event.
     */
    public function created(MonthlySalesReport $monthlyReport): void
    {
        try {
            $year = $monthlyReport->year;
            
            Log::info("Monthly sales report created for {$year}-{$monthlyReport->month}, triggering yearly report update for {$year}");
            
            // Update yearly report for this year (creates if doesn't exist)
            $this->reportService->generateYearlyReport($year);
            
            Log::info("Successfully updated yearly report for {$year} after monthly report creation");
        } catch (\Exception $e) {
            Log::error("Failed to update yearly report after monthly report creation: " . $e->getMessage());
            // Don't throw the exception to prevent the monthly report creation from failing
        }
    }

    /**
     * Handle the MonthlySalesReport "updated" event.
     */
    public function updated(MonthlySalesReport $monthlyReport): void
    {
        try {
            $year = $monthlyReport->year;
            
            Log::info("Monthly sales report updated for {$year}-{$monthlyReport->month}, triggering yearly report update for {$year}");
            
            // Update yearly report for this year (creates if doesn't exist)
            $this->reportService->generateYearlyReport($year);
            
            Log::info("Successfully updated yearly report for {$year} after monthly report update");
        } catch (\Exception $e) {
            Log::error("Failed to update yearly report after monthly report update: " . $e->getMessage());
        }
    }

    /**
     * Handle the MonthlySalesReport "deleted" event.
     */
    public function deleted(MonthlySalesReport $monthlyReport): void
    {
        try {
            $year = $monthlyReport->year;
            
            Log::info("Monthly sales report deleted for {$year}-{$monthlyReport->month}, triggering yearly report update for {$year}");
            
            // Update yearly report for this year (creates if doesn't exist)
            $this->reportService->generateYearlyReport($year);
            
            Log::info("Successfully updated yearly report for {$year} after monthly report deletion");
        } catch (\Exception $e) {
            Log::error("Failed to update yearly report after monthly report deletion: " . $e->getMessage());
        }
    }
} 