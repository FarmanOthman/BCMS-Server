<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Log;

class SaleObserver
{
    protected $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        try {
            Log::info("Sale created for car {$sale->car_id}, triggering report generation for date: {$sale->sale_date}");
            
            // Generate reports for the sale date
            $this->reportService->generateReportsForSale($sale->sale_date);
            
            Log::info("Successfully generated reports for sale date: {$sale->sale_date}");
        } catch (\Exception $e) {
            Log::error("Failed to generate reports for sale date {$sale->sale_date}: " . $e->getMessage());
            // Don't throw the exception to prevent the sale creation from failing
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Only regenerate reports if the sale_date changed
        if ($sale->wasChanged('sale_date')) {
            try {
                $oldDate = $sale->getOriginal('sale_date');
                $newDate = $sale->sale_date;
                
                Log::info("Sale date changed from {$oldDate} to {$newDate}, regenerating reports");
                
                // Generate reports for both the old and new dates
                $this->reportService->generateReportsForSale($oldDate);
                $this->reportService->generateReportsForSale($newDate);
                
                Log::info("Successfully regenerated reports for both dates");
            } catch (\Exception $e) {
                Log::error("Failed to regenerate reports after sale date change: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        try {
            Log::info("Sale deleted for car {$sale->car_id}, regenerating reports for date: {$sale->sale_date}");
            
            // Regenerate reports for the sale date since the sale was removed
            $this->reportService->generateReportsForSale($sale->sale_date);
            
            Log::info("Successfully regenerated reports after sale deletion for date: {$sale->sale_date}");
        } catch (\Exception $e) {
            Log::error("Failed to regenerate reports after sale deletion: " . $e->getMessage());
        }
    }
} 