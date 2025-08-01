<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Services\ReportGenerationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckMissingReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:check-missing {--from= : Start date to check from (YYYY-MM-DD)} {--to= : End date to check to (YYYY-MM-DD)} {--dry-run : Show what would be generated without actually generating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for missing reports based on existing sales data and generate them automatically.';

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
        $fromDate = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subYear();
        $toDate = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $dryRun = $this->option('dry-run');

        $this->info("Checking for missing reports from {$fromDate->format('Y-m-d')} to {$toDate->format('Y-m-d')}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No reports will be actually generated");
        }

        // Get all unique sale dates in the range
        $saleDates = Sale::whereBetween('sale_date', [$fromDate, $toDate])
            ->distinct()
            ->pluck('sale_date')
            ->sort()
            ->values();

        if ($saleDates->isEmpty()) {
            $this->info("No sales found in the specified date range.");
            return 0;
        }

        $this->info("Found {$saleDates->count()} unique sale dates with sales data.");

        $generatedCount = 0;
        $errorCount = 0;

        foreach ($saleDates as $saleDate) {
            try {
                $this->line("Processing date: {$saleDate}");
                
                if (!$dryRun) {
                    $this->reportService->forceGenerateReportsForSale($saleDate);
                    $generatedCount++;
                    $this->info("✓ Generated reports for {$saleDate}");
                } else {
                    $this->info("✓ Would generate reports for {$saleDate}");
                    $generatedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to generate reports for {$saleDate}: " . $e->getMessage());
                Log::error("Failed to generate reports for {$saleDate}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("- Total sale dates processed: {$saleDates->count()}");
        $this->info("- Reports generated: {$generatedCount}");
        $this->info("- Errors: {$errorCount}");

        if ($errorCount > 0) {
            $this->warn("Some reports failed to generate. Check the logs for details.");
            return 1;
        }

        $this->info("All reports processed successfully!");
        return 0;
    }
} 