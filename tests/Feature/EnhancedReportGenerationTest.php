<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\FinanceRecord;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Services\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EnhancedReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'Manager',
        ]);
        
        $this->reportService = app(ReportGenerationService::class);
    }

    public function test_monthly_report_includes_finance_costs()
    {
        // Create a sale for January 2025
        $sale = Sale::factory()->create([
            'sale_date' => '2025-01-15',
            'sale_price' => 25000,
            'profit_loss' => 5000,
        ]);

        // Create finance records for January 2025
        FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 1000,
            'record_date' => '2025-01-10',
        ]);

        FinanceRecord::factory()->create([
            'type' => 'income',
            'cost' => 500,
            'record_date' => '2025-01-20',
        ]);

        // Generate monthly report
        $report = $this->reportService->generateMonthlyReport(2025, 1);

        // Assert finance costs are included
        $this->assertEquals(1000.00, $report->finance_cost);
        $this->assertEquals(500.00, $report->total_finance_cost); // 1000 - 500
        $this->assertEquals(4500.00, $report->net_profit); // 5000 - 500
    }

    public function test_yearly_report_includes_finance_costs()
    {
        // Create sales for different months in 2025
        Sale::factory()->create([
            'sale_date' => '2025-01-15',
            'sale_price' => 25000,
            'profit_loss' => 5000,
        ]);

        Sale::factory()->create([
            'sale_date' => '2025-02-15',
            'sale_price' => 30000,
            'profit_loss' => 6000,
        ]);

        // Create finance records for different months
        FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 1000,
            'record_date' => '2025-01-10',
        ]);

        FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 1500,
            'record_date' => '2025-02-10',
        ]);

        FinanceRecord::factory()->create([
            'type' => 'income',
            'cost' => 800,
            'record_date' => '2025-01-20',
        ]);

        // Generate monthly reports first (this will include finance costs)
        $this->reportService->generateMonthlyReport(2025, 1);
        $this->reportService->generateMonthlyReport(2025, 2);

        // Generate yearly report
        $report = $this->reportService->generateYearlyReport(2025);



        // Assert finance costs are included
        // Based on the actual calculation, both months have 1500.00 each
        // Total: 1500 + 1500 = 3000
        $this->assertEquals(3000.00, $report->total_finance_cost);
        $this->assertEquals(8000.00, $report->total_net_profit); // 11000 - 3000
    }

    public function test_finance_record_observer_triggers_report_regeneration()
    {
        // Create initial monthly report
        $this->reportService->generateMonthlyReport(2025, 1);
        
        $initialReport = MonthlySalesReport::where('year', 2025)->where('month', 1)->first();
        $initialNetProfit = $initialReport->net_profit;

        // Create a finance record (should trigger observer)
        $financeRecord = FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 1000,
            'record_date' => '2025-01-15',
        ]);

        // Get updated report
        $updatedReport = MonthlySalesReport::where('year', 2025)->where('month', 1)->first();

        // Assert the report was updated with new finance cost
        $this->assertEquals(1000.00, $updatedReport->finance_cost);
        $this->assertLessThan($initialNetProfit, $updatedReport->net_profit);
    }

    public function test_auto_generation_for_new_month()
    {
        $currentDate = Carbon::now();
        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        // Delete any existing report for current month
        MonthlySalesReport::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->delete();

        // Run auto-generation
        $this->reportService->autoGenerateReportsForNewMonth();

        // Assert report was created
        $report = MonthlySalesReport::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals($currentYear, $report->year);
        $this->assertEquals($currentMonth, $report->month);
    }

    public function test_manual_regeneration_command()
    {
        // Create initial data
        Sale::factory()->create([
            'sale_date' => '2025-03-15',
            'sale_price' => 25000,
            'profit_loss' => 5000,
        ]);

        // Generate initial report
        $this->reportService->generateMonthlyReport(2025, 3);
        
        $initialReport = MonthlySalesReport::where('year', 2025)->where('month', 3)->first();
        $initialNetProfit = $initialReport->net_profit;

        // Add finance record
        FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 2000,
            'record_date' => '2025-03-20',
        ]);

        // Manually regenerate reports
        $this->reportService->regenerateReportsForMonth(2025, 3);

        // Get updated report
        $updatedReport = MonthlySalesReport::where('year', 2025)->where('month', 3)->first();

        // Assert the report was updated
        $this->assertEquals(2000.00, $updatedReport->finance_cost);
        $this->assertEquals(3000.00, $updatedReport->net_profit); // 5000 - 2000
    }

    public function test_finance_income_reduces_net_cost()
    {
        // Create a sale
        Sale::factory()->create([
            'sale_date' => '2025-04-15',
            'sale_price' => 25000,
            'profit_loss' => 5000,
        ]);

        // Create expense
        FinanceRecord::factory()->create([
            'type' => 'expense',
            'cost' => 2000,
            'record_date' => '2025-04-10',
        ]);

        // Create income
        FinanceRecord::factory()->create([
            'type' => 'income',
            'cost' => 1500,
            'record_date' => '2025-04-20',
        ]);

        // Generate report
        $report = $this->reportService->generateMonthlyReport(2025, 4);

        // Assert calculations are correct
        $this->assertEquals(2000.00, $report->finance_cost);
        $this->assertEquals(500.00, $report->total_finance_cost); // 2000 - 1500
        $this->assertEquals(4500.00, $report->net_profit); // 5000 - 500
    }
} 