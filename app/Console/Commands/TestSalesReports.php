<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class TestSalesReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:test {date? : The date to generate reports for (YYYY-MM-DD). Defaults to yesterday.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test generating both daily and monthly sales reports with the same logic as the tests.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $dateInput = $this->argument('date');
            $targetDate = $dateInput ? Carbon::parse($dateInput) : Carbon::yesterday();
            
            $this->info("===== GENERATING SALES REPORTS FOR {$targetDate->format('Y-m-d')} =====");
            
            // Generate daily report
            $this->info("\n1. Generating Daily Sales Report...");
            $this->call('reports:generate-daily', [
                'date' => $targetDate->format('Y-m-d')
            ]);
            
            // Generate monthly report for the same month
            $this->info("\n2. Generating Monthly Sales Report...");
            $this->call('reports:generate-monthly', [
                'year' => $targetDate->year,
                'month' => $targetDate->month
            ]);
            
            $this->info("\n===== REPORT GENERATION COMPLETE =====");
            $this->info("Sales reports have been generated for daily ({$targetDate->format('Y-m-d')}) and monthly ({$targetDate->format('Y-m')}) periods.");
            $this->info("You can now view these reports in the database or through the API.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
