<?php

namespace Tests\Feature\Commands;

use App\Models\FinanceRecord;
use App\Models\MonthlySalesReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMonthlyFinanceCostsTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_command_updates_total_finance_cost()
    {
        // Create a user
        $user = User::factory()->create(['role' => 'Manager']);
        
        // Create a monthly report
        $report = MonthlySalesReport::create([
            'year' => 2025,
            'month' => 6,
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-30',
            'total_sales' => 10,
            'total_revenue' => 100000,
            'total_profit' => 30000,
            'avg_daily_profit' => 1000,
            'best_day' => '2025-06-15',
            'best_day_profit' => 5000,
            'profit_margin' => 30.0,
            'finance_cost' => 5000,
            'total_finance_cost' => 0, // Initial value is 0
            'net_profit' => 25000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // Create some finance records for June 2025
        FinanceRecord::create([
            'type' => 'Expense',
            'category' => 'Utilities',
            'cost' => 2500,
            'record_date' => '2025-06-10',
            'description' => 'Electricity bill',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        FinanceRecord::create([
            'type' => 'Expense',
            'category' => 'Rent',
            'cost' => 5000,
            'record_date' => '2025-06-15',
            'description' => 'Office rent',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // Expected total finance cost
        $expectedTotalFinanceCost = 7500;
        
        // Run the command
        $this->artisan('reports:update-monthly-finance-costs')
             ->expectsOutput("Found 1 monthly reports to update.")
             ->expectsOutput("Found 2 finance records for 2025-6, total cost: 7500")
             ->expectsOutput("Updated report for 2025-6: total_finance_cost set to 7500, net_profit set to 22500")
             ->expectsOutput("Update complete. Updated 1 reports, skipped 0 reports.")
             ->assertExitCode(0);
        
        // Refresh the report
        $report->refresh();
        
        // Assert that the total_finance_cost was updated
        $this->assertEquals($expectedTotalFinanceCost, $report->total_finance_cost);
        
        // Assert that the net_profit was recalculated
        $this->assertEquals($report->total_profit - $expectedTotalFinanceCost, $report->net_profit);
        
        // Run the command again, it should now skip the report
        $this->artisan('reports:update-monthly-finance-costs')
             ->expectsOutput("Found 1 monthly reports to update.")
             ->expectsOutput("Found 2 finance records for 2025-6, total cost: 7500")
             ->expectsOutput("Report for 2025-6 already has correct total_finance_cost (7500)")
             ->expectsOutput("Update complete. Updated 0 reports, skipped 1 reports.")
             ->assertExitCode(0);
    }
    
    public function test_command_handles_no_finance_records()
    {
        // Create a user
        $user = User::factory()->create(['role' => 'Manager']);
        
        // Create a monthly report
        $report = MonthlySalesReport::create([
            'year' => 2025,
            'month' => 7,
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-31',
            'total_sales' => 5,
            'total_revenue' => 50000,
            'total_profit' => 15000,
            'avg_daily_profit' => 500,
            'best_day' => '2025-07-15',
            'best_day_profit' => 2500,
            'profit_margin' => 30.0,
            'finance_cost' => 2000,
            'total_finance_cost' => 1000, // Incorrect value
            'net_profit' => 13000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // No finance records for July 2025
        
        // Run the command
        $this->artisan('reports:update-monthly-finance-costs')
             ->expectsOutput("Found 1 monthly reports to update.")
             ->expectsOutput("No finance records found for 2025-7, setting total_finance_cost to 0")
             ->expectsOutput("Updated report for 2025-7: total_finance_cost set to 0, net_profit set to 15000")
             ->expectsOutput("Update complete. Updated 1 reports, skipped 0 reports.")
             ->assertExitCode(0);
        
        // Refresh the report
        $report->refresh();
        
        // Assert that the total_finance_cost was updated to 0
        $this->assertEquals(0, $report->total_finance_cost);
        
        // Assert that the net_profit was recalculated
        $this->assertEquals($report->total_profit, $report->net_profit);
    }
}
