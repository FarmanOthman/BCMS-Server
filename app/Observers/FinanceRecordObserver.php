<?php

namespace App\Observers;

use App\Models\FinanceRecord;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Log;

class FinanceRecordObserver
{
    protected $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Handle the FinanceRecord "created" event.
     */
    public function created(FinanceRecord $financeRecord): void
    {
        Log::info("Finance record created: {$financeRecord->description} for {$financeRecord->record_date}");
        $this->regenerateReportsForFinanceRecord($financeRecord);
    }

    /**
     * Handle the FinanceRecord "updated" event.
     */
    public function updated(FinanceRecord $financeRecord): void
    {
        Log::info("Finance record updated: {$financeRecord->description} for {$financeRecord->record_date}");
        $this->regenerateReportsForFinanceRecord($financeRecord);
    }

    /**
     * Handle the FinanceRecord "deleted" event.
     */
    public function deleted(FinanceRecord $financeRecord): void
    {
        Log::info("Finance record deleted: {$financeRecord->description} for {$financeRecord->record_date}");
        $this->regenerateReportsForFinanceRecord($financeRecord);
    }

    /**
     * Regenerate reports for the month/year of the finance record
     */
    protected function regenerateReportsForFinanceRecord(FinanceRecord $financeRecord): void
    {
        try {
            $recordDate = $financeRecord->record_date;
            $year = $recordDate->year;
            $month = $recordDate->month;

            Log::info("Regenerating reports for finance record in {$year}-{$month}");
            
            // Regenerate monthly and yearly reports
            $this->reportService->regenerateReportsForMonth($year, $month);
            
        } catch (\Exception $e) {
            Log::error("Failed to regenerate reports for finance record: " . $e->getMessage());
        }
    }
} 