<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Log;

class InitializeReportTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:initialize-tracker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the report generation tracker with existing report data.';

    protected $reportService;

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
        $this->info('Initializing report generation tracker...');

        try {
            $this->reportService->initializeTracker();
            $this->info('✓ Successfully initialized report generation tracker');
            $this->info('The tracker now knows about existing reports and will only generate new ones when needed.');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to initialize tracker: ' . $e->getMessage());
            Log::error('Failed to initialize tracker: ' . $e->getMessage());
            return 1;
        }
    }
} 