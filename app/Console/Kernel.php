<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
// Add the import for your command
use App\Console\Commands\GenerateDailySalesReport;
use App\Console\Commands\GenerateMonthlySalesReport; // Added import for Monthly Report
use App\Console\Commands\GenerateYearlySalesReport; // Added import for Yearly Report
use App\Console\Commands\TestSalesReports; // Added import for test command
use App\Console\Commands\UpdateMonthlyFinanceCosts; // Added import for updating finance costs

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */    protected $commands = [
        // ...existing commands...
        GenerateDailySalesReport::class, // Register the command
        GenerateMonthlySalesReport::class, // Register the Monthly Report command
        GenerateYearlySalesReport::class, // Register the Yearly Report command
        TestSalesReports::class, // Register the test command
        UpdateMonthlyFinanceCosts::class, // Register the finance cost update command
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Schedule the daily sales report to run daily at a specific time, e.g., 1:00 AM
        $schedule->command('reports:generate-daily')->dailyAt('01:00');

        // Schedule the monthly sales report to run on the 1st day of every month at 2:00 AM
        $schedule->command('reports:generate-monthly')->monthlyOn(1, '02:00');

        // Schedule the yearly sales report to run on January 1st at 3:00 AM
        $schedule->command('reports:generate-yearly')->yearlyOn(1, 1, '03:00');
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
