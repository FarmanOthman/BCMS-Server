<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonthlySalesReport;
use App\Models\FinanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateExistingMonthlySalesReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:update-monthly-finance-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing monthly sales reports to ensure total_finance_cost is set correctly.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $reports = MonthlySalesReport::all();
            $this->info("Found " . $reports->count() . " monthly reports to update.");

            $updated = 0;
            $skipped = 0;

            foreach ($reports as $report) {
                $year = $report->year;
                $month = $report->month;
                
                $this->info("Processing report for {$year}-{$month}...");
                
                // Get finance records for the month to calculate finance costs
                $financeRecords = FinanceRecord::whereYear('record_date', $year)
                                              ->whereMonth('record_date', $month)
                                              ->get();
                $financeCost = $financeRecords->sum('cost');
                
                // Check if the value is already correct
                if (round((float)$report->total_finance_cost, 2) == round((float)$financeCost, 2)) {
                    $this->info("Report for {$year}-{$month} already has correct total_finance_cost.");
                    $skipped++;
                    continue;
                }
                
                // Update the report with the correct finance cost
                $report->total_finance_cost = round((float)$financeCost, 2);
                
                // Also ensure net_profit is calculated correctly
                $report->net_profit = round((float)$report->total_profit - (float)$financeCost, 2);
                
                $report->save();
                
                $this->info("Updated report for {$year}-{$month}:");
                $this->info("- Total Finance Cost: \$" . $report->total_finance_cost);
                $this->info("- Net Profit: \$" . $report->net_profit);
                
                $updated++;
            }
            
            $this->info("Update complete. Updated {$updated} reports, skipped {$skipped} reports.");
            return 0;

        } catch (\Exception $e) {
            Log::error("Error updating monthly sales reports: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            $this->error("An error occurred while updating monthly reports: " . $e->getMessage());
            return 1;
        }
    }
}
