<?php

namespace App\Console\Commands;

use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateReportsForMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:regenerate-month {year} {month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate monthly and yearly reports for a specific month (useful when finance records are added)';

    protected $reportService;

    /**
     * Create a new command instance.
     */
    public function __construct(ReportGenerationService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = (int) $this->argument('year');
        $month = (int) $this->argument('month');
        
        // Validate inputs
        if ($year < 2000 || $year > 2100) {
            $this->error('Invalid year. Please provide a year between 2000 and 2100.');
            return 1;
        }
        
        if ($month < 1 || $month > 12) {
            $this->error('Invalid month. Please provide a month between 1 and 12.');
            return 1;
        }
        
        $this->info("Regenerating reports for {$year}-{$month}...");
        
        try {
            $this->reportService->regenerateReportsForMonth($year, $month);
            $this->info("Successfully regenerated reports for {$year}-{$month}!");
            
            Log::info("Manual regeneration of reports completed for {$year}-{$month}");
            
        } catch (\Exception $e) {
            $this->error("Failed to regenerate reports for {$year}-{$month}: " . $e->getMessage());
            Log::error("Manual regeneration of reports failed for {$year}-{$month}: " . $e->getMessage());
            
            return 1;
        }
        
        return 0;
    }
} 