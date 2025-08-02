<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // All report generation is now handled by observers
        // No manual commands needed for automatic report generation
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // All report generation is now handled automatically by observers:
        // - SaleObserver: Triggers when sales are created/updated/deleted
        // - FinanceRecordObserver: Triggers when finance records change
        // - DailySalesReportObserver: Triggers when daily reports change
        // - MonthlySalesReportObserver: Triggers when monthly reports change
        
        // No scheduled commands needed - real-time updates via observers
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
