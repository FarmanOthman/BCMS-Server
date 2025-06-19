<?php

namespace App\Console\Commands;

use App\Models\FinanceRecord;
use App\Models\MonthlySalesReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateMonthlyFinanceCosts extends Command
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
    protected $description = 'Updates the total_finance_cost field for all monthly sales reports based on finance records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reports = MonthlySalesReport::all();
        
        $this->info("Found " . $reports->count() . " monthly reports to update.");
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($reports as $report) {
            $year = $report->year;
            $month = $report->month;
            
            // Calculate finance costs for this month
            $financeRecords = FinanceRecord::whereYear('record_date', $year)
                                          ->whereMonth('record_date', $month)
                                          ->get();
            
            if ($financeRecords->isEmpty()) {
                $this->info("No finance records found for {$year}-{$month}, setting total_finance_cost to 0");
                $totalFinanceCost = 0;
            } else {
                $totalFinanceCost = $financeRecords->sum('cost');
                $this->info("Found " . $financeRecords->count() . " finance records for {$year}-{$month}, total cost: {$totalFinanceCost}");
            }
              // Only update if necessary
            if ($report->total_finance_cost != $totalFinanceCost) {
                // Calculate new net profit
                $newNetProfit = round((float)($report->total_profit - $totalFinanceCost), 2);
                
                DB::transaction(function () use ($report, $totalFinanceCost, $newNetProfit) {
                    $report->total_finance_cost = round((float)$totalFinanceCost, 2);
                    // Update net profit as well
                    $report->net_profit = $newNetProfit;
                    
                    $report->save();
                });
                
                $this->info("Updated report for {$year}-{$month}: total_finance_cost set to {$totalFinanceCost}, net_profit set to {$newNetProfit}");
                $updated++;
            } else {
                $this->info("Report for {$year}-{$month} already has correct total_finance_cost ({$totalFinanceCost})");
                $skipped++;
            }
        }
        
        $this->info("Update complete. Updated {$updated} reports, skipped {$skipped} reports.");
        return 0;
    }
}
