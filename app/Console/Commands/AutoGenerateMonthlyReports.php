<?php

namespace App\Console\Commands;

use App\Services\ReportGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoGenerateMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:auto-generate-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate monthly and yearly reports for new months';

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
        $this->info('Starting auto-generation of monthly reports...');
        
        try {
            $this->reportService->autoGenerateReportsForNewMonth();
            $this->info('Auto-generation completed successfully!');
            
            Log::info('Auto-generation of monthly reports completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Failed to auto-generate monthly reports: ' . $e->getMessage());
            Log::error('Auto-generation of monthly reports failed: ' . $e->getMessage());
            
            return 1;
        }
        
        return 0;
    }
} 