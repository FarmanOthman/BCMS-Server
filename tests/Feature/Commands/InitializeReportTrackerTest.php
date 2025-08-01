<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Models\ReportGenerationTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InitializeReportTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create(['role' => 'Manager']);
    }

    public function test_initialize_tracker_with_no_existing_reports()
    {
        $this->artisan('reports:initialize-tracker')
            ->expectsOutput('Initializing report generation tracker...')
            ->expectsOutput('✓ Successfully initialized report generation tracker')
            ->expectsOutput('The tracker now knows about existing reports and will only generate new ones when needed.')
            ->assertExitCode(0);

        // Verify tracker was created with null values
        $tracker = ReportGenerationTracker::getInstance();
        $this->assertNull($tracker->last_daily_report_date);
        $this->assertNull($tracker->last_monthly_report_year);
        $this->assertNull($tracker->last_monthly_report_month);
        $this->assertNull($tracker->last_yearly_report_year);
    }

    public function test_initialize_tracker_with_existing_reports()
    {
        // Create some existing reports
        $dailyReport = DailySalesReport::factory()->create([
            'report_date' => '2024-01-15',
        ]);

        $monthlyReport = MonthlySalesReport::factory()->create([
            'year' => 2024,
            'month' => 1,
        ]);

        $yearlyReport = YearlySalesReport::factory()->create([
            'year' => 2024,
        ]);

        $this->artisan('reports:initialize-tracker')
            ->expectsOutput('Initializing report generation tracker...')
            ->expectsOutput('✓ Successfully initialized report generation tracker')
            ->expectsOutput('The tracker now knows about existing reports and will only generate new ones when needed.')
            ->assertExitCode(0);

        // Verify tracker was initialized with the latest report dates
        $tracker = ReportGenerationTracker::getInstance();
        $this->assertEquals('2024-01-15', $tracker->last_daily_report_date->format('Y-m-d'));
        $this->assertEquals(2024, $tracker->last_monthly_report_year);
        $this->assertEquals(1, $tracker->last_monthly_report_month);
        $this->assertEquals(2024, $tracker->last_yearly_report_year);
    }

    public function test_initialize_tracker_with_multiple_reports()
    {
        // Create multiple reports with different dates
        DailySalesReport::factory()->create(['report_date' => '2024-01-10']);
        DailySalesReport::factory()->create(['report_date' => '2024-01-15']);
        DailySalesReport::factory()->create(['report_date' => '2024-01-20']);

        MonthlySalesReport::factory()->create(['year' => 2023, 'month' => 12]);
        MonthlySalesReport::factory()->create(['year' => 2024, 'month' => 1]);
        MonthlySalesReport::factory()->create(['year' => 2024, 'month' => 2]);

        YearlySalesReport::factory()->create(['year' => 2023]);
        YearlySalesReport::factory()->create(['year' => 2024]);

        $this->artisan('reports:initialize-tracker')
            ->expectsOutput('Initializing report generation tracker...')
            ->expectsOutput('✓ Successfully initialized report generation tracker')
            ->expectsOutput('The tracker now knows about existing reports and will only generate new ones when needed.')
            ->assertExitCode(0);

        // Verify tracker was initialized with the latest report dates
        $tracker = ReportGenerationTracker::getInstance();
        $this->assertEquals('2024-01-20', $tracker->last_daily_report_date->format('Y-m-d'));
        $this->assertEquals(2024, $tracker->last_monthly_report_year);
        $this->assertEquals(2, $tracker->last_monthly_report_month);
        $this->assertEquals(2024, $tracker->last_yearly_report_year);
    }
} 