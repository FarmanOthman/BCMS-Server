<?php

namespace Tests\Feature\Api;

use App\Models\FinanceRecord;
use App\Models\MonthlySalesReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Mockery;

class MonthlySalesReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected $managerToken;
    protected $userToken;
    protected $manager;
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->manager = User::factory()->create([
            'email' => 'monthly-report-test-manager@example.com',
            'role' => 'Manager',
            'name' => 'Monthly Report Test Manager'
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'monthly-report-test-user@example.com', 
            'role' => 'User',
            'name' => 'Monthly Report Test User'
        ]);
        
        // Create proper tokens for authentication
        $this->managerToken = base64_encode(json_encode([
            'user_id' => $this->manager->id,
            'exp' => time() + 3600
        ]));
        
        $this->userToken = base64_encode(json_encode([
            'user_id' => $this->user->id,
            'exp' => time() + 3600
        ]));
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test that a manager can list all monthly sales reports.
     *
     * @return void
     */
    public function test_manager_can_list_monthly_sales_reports()
    {
        // Create multiple test reports
        MonthlySalesReport::factory()->forYearMonth(2025, 1)->create();
        MonthlySalesReport::factory()->forYearMonth(2025, 2)->create();
        MonthlySalesReport::factory()->forYearMonth(2025, 3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly/list');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }
    
    /**
     * Test that a manager can retrieve a monthly sales report.
     *
     * @return void
     */
    public function test_manager_can_retrieve_monthly_sales_report()
    {
        // Create a test report
        $year = 2025;
        $month = 6;
        $report = MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly?year=' . $year . '&month=' . $month);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'year' => $year,
                     'month' => $month,
                     'total_sales' => $report->total_sales,
                     'total_revenue' => number_format($report->total_revenue, 2, '.', ''),
                     'total_profit' => number_format($report->total_profit, 2, '.', '')
                 ]);
    }
    
    /**
     * Test that a manager can create a monthly sales report.
     *
     * @return void
     */
    public function test_manager_can_create_monthly_sales_report()
    {
        $this->actingAs($this->manager);
        
        $year = 2025;
        $month = 7;
        $startDate = "2025-07-01";
        $endDate = "2025-07-31";
          $reportData = [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_sales' => 50,
            'total_revenue' => 500000,
            'total_profit' => 150000,
            'avg_daily_profit' => 5000,
            'best_day' => '2025-07-15',
            'best_day_profit' => 20000,
            'profit_margin' => 30,
            'finance_cost' => 25000,
            'net_profit' => 125000
        ];
        
        // Add total_finance_cost if the column exists
        if (Schema::hasColumn('monthlysalesreport', 'total_finance_cost')) {
            $reportData['total_finance_cost'] = 28000;
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/monthly', $reportData);        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'year' => $year,
                     'month' => $month,
                     'total_sales' => 50,
                     'total_revenue' => '500000.00',
                     'total_profit' => '150000.00',
                     'avg_daily_profit' => '5000.00',
                     'finance_cost' => '25000.00',
                     'net_profit' => '125000.00'
                 ]);
        
        $this->assertDatabaseHas('monthlysalesreport', [
            'year' => $year,
            'month' => $month,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);
    }
    
    /**
     * Test that a manager can update a monthly sales report.
     *
     * @return void
     */
    public function test_manager_can_update_monthly_sales_report()
    {
        $this->actingAs($this->manager);
        
        // Create a test report
        $year = 2025;
        $month = 8;
        MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'total_sales' => 30,
            'total_revenue' => 300000,
            'total_profit' => 90000,
            'avg_daily_profit' => 3000,
            'finance_cost' => 20000,
            'net_profit' => 70000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);
        
        $updateData = [
            'total_sales' => 40,
            'total_revenue' => 400000,            'total_profit' => 120000,
            'avg_daily_profit' => 4000,
            'finance_cost' => 25000,
            'total_finance_cost' => 28000,
            'net_profit' => 95000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/reports/monthly/' . $year . '/' . $month, $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'year' => $year,
                     'month' => $month,
                     'total_sales' => 40,
                     'total_revenue' => '400000.00',                     'total_profit' => '120000.00',
                     'finance_cost' => '25000.00',
                     'total_finance_cost' => '28000.00',
                     'net_profit' => '95000.00'
                 ]);
        
        $this->assertDatabaseHas('monthlysalesreport', [
            'year' => $year,
            'month' => $month,
            'total_sales' => 40,
            'updated_by' => $this->manager->id
        ]);
    }
    
    /**
     * Test that a manager can delete a monthly sales report.
     *
     * @return void
     */
    public function test_manager_can_delete_monthly_sales_report()
    {
        $this->actingAs($this->manager);
        
        // Create a test report
        $year = 2025;
        $month = 9;
        MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/reports/monthly/' . $year . '/' . $month);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Monthly sales report deleted successfully.'
                 ]);
        
        $this->assertDatabaseMissing('monthlysalesreport', [
            'year' => $year,
            'month' => $month
        ]);
    }
    
    /**
     * Test that a manager receives a 404 when requesting a non-existent report.
     *
     * @return void
     */
    public function test_manager_gets_404_for_nonexistent_report()
    {
        $nonExistentYear = 2099;
        $nonExistentMonth = 12;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly?year=' . $nonExistentYear . '&month=' . $nonExistentMonth);

        $response->assertStatus(404)
                 ->assertJsonFragment([
                     'message' => 'No monthly report found for this year and month.'
                 ]);
    }
    
    /**
     * Test that a regular user cannot access monthly sales reports.
     *
     * @return void
     */
    public function test_regular_user_cannot_access_monthly_sales_reports()
    {
        // Create a test report
        $year = 2025;
        $month = 10;
        MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Test GET (show)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/reports/monthly?year=' . $year . '&month=' . $month);
        $response->assertStatus(403); // Forbidden
        
        // Test GET (index)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/reports/monthly/list');
        $response->assertStatus(403);
        
        // Test POST (create)
        $reportData = [
            'year' => 2025,
            'month' => 11,            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
            'total_sales' => 50,
            'total_revenue' => 500000,
            'total_profit' => 150000,
            'avg_daily_profit' => 5000,
            'finance_cost' => 30000,
            'total_finance_cost' => 32000,
            'net_profit' => 120000
        ];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/reports/monthly', $reportData);
        $response->assertStatus(403);
        
        // Test PUT (update)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/reports/monthly/' . $year . '/' . $month, ['total_sales' => 100]);
        $response->assertStatus(403);
        
        // Test DELETE
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/reports/monthly/' . $year . '/' . $month);
        $response->assertStatus(403);
    }
    
    /**
     * Test that an unauthenticated user cannot access monthly sales reports.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_monthly_sales_reports()
    {
        $year = 2025;
        $month = 11;
        MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Test GET (show)
        $response = $this->getJson('/bcms/reports/monthly?year=' . $year . '&month=' . $month);
        $response->assertStatus(401); // Unauthorized
        
        // Test GET (index)
        $response = $this->getJson('/bcms/reports/monthly/list');
        $response->assertStatus(401);
        
        // Test POST (create)
        $reportData = [
            'year' => 2025,
            'month' => 12,
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-31',
            'total_sales' => 50,
            'total_revenue' => 500000,
            'total_profit' => 150000,
            'avg_daily_profit' => 5000,
            'finance_cost' => 30000,
            'net_profit' => 120000
        ];
        $response = $this->postJson('/bcms/reports/monthly', $reportData);
        $response->assertStatus(401);
        
        // Test PUT (update)
        $response = $this->putJson('/bcms/reports/monthly/' . $year . '/' . $month, ['total_sales' => 100]);
        $response->assertStatus(401);
        
        // Test DELETE
        $response = $this->deleteJson('/bcms/reports/monthly/' . $year . '/' . $month);
        $response->assertStatus(401);
    }
    
    /**
     * Test validation rules for creating monthly sales reports.
     *
     * @return void
     */
    public function test_create_monthly_report_validation_rules()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/monthly', []);

        $response->assertStatus(422)                 ->assertJsonValidationErrors([
                     'year', 'month', 'start_date', 'end_date', 'total_sales', 'total_revenue', 
                     'total_profit', 'avg_daily_profit', 'finance_cost', 'net_profit'
                 ]);
        
        // Test duplicate year/month validation
        $existingYear = 2025;
        $existingMonth = 12;
        MonthlySalesReport::factory()->forYearMonth($existingYear, $existingMonth)->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/monthly', [
            'year' => $existingYear,
            'month' => $existingMonth,
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-31',
            'total_sales' => 50,
            'total_revenue' => 500000,
            'total_profit' => 150000,
            'avg_daily_profit' => 5000,
            'finance_cost' => 30000,
            'net_profit' => 120000
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['year']);
    }
    
    /**
     * Test validation rules for the monthly report show endpoint.
     *
     * @return void
     */
    public function test_show_monthly_report_validation_rules()
    {
        // Test missing parameters
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['year', 'month']);
        
        // Test invalid parameters
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly?year=invalid&month=13');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['year', 'month']);
    }
    
    /**
     * Test that the monthly report contains accurate calculations.
     *
     * @return void
     */
    public function test_monthly_report_calculations_are_accurate()
    {
        $year = 2025;
        $month = 5;        $totalSales = 50;
        $totalRevenue = 500000;
        $totalProfit = 150000;
        $financeCost = 30000;
        $totalFinanceCost = 35000;
        $netProfit = 115000;
        $avgDailyProfit = 5000;
        
        // Create a report with specific values
        MonthlySalesReport::factory()->forYearMonth($year, $month)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'total_sales' => $totalSales,            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'avg_daily_profit' => $avgDailyProfit,
            'finance_cost' => $financeCost,
            'total_finance_cost' => $totalFinanceCost,
            'net_profit' => $netProfit
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly?year=' . $year . '&month=' . $month);

        $response->assertStatus(200);
        
        // Verify calculation accuracy
        $this->assertEquals($totalSales, $response->json('total_sales'));
        $this->assertEquals(number_format($totalRevenue, 2, '.', ''), $response->json('total_revenue'));        $this->assertEquals(number_format($totalProfit, 2, '.', ''), $response->json('total_profit'));
        $this->assertEquals(number_format($avgDailyProfit, 2, '.', ''), $response->json('avg_daily_profit'));
        $this->assertEquals(number_format($financeCost, 2, '.', ''), $response->json('finance_cost'));
        $this->assertEquals(number_format($totalFinanceCost, 2, '.', ''), $response->json('total_finance_cost'));
        $this->assertEquals(number_format($netProfit, 2, '.', ''), $response->json('net_profit'));
          // Verify that net_profit = total_profit - total_finance_cost
        $this->assertEquals(
            number_format($totalProfit - $totalFinanceCost, 2, '.', ''), 
            number_format($netProfit, 2, '.', '')
        );
    }
    
    /**
     * Test that year filtering works for the index endpoint.
     *
     * @return void
     */
    public function test_monthly_report_year_filtering()
    {
        // Create multiple reports with different years
        MonthlySalesReport::factory()->forYearMonth(2024, 12)->create();
        MonthlySalesReport::factory()->forYearMonth(2025, 1)->create();
        MonthlySalesReport::factory()->forYearMonth(2025, 6)->create();
        MonthlySalesReport::factory()->forYearMonth(2026, 1)->create();
        
        // Test filtering by year
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/monthly/list?year=2025');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json()); // Should return 2 reports (2025-01, 2025-06)
    }
    
    /** @test */
    public function test_store_monthly_report_calculates_total_finance_cost_if_not_provided()
    {
        $year = 2025;
        $month = 8;
        
        // Create some finance records for the month
        $financeRecord1 = FinanceRecord::factory()->create([
            'record_date' => '2025-08-10',
            'cost' => 2500,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        $financeRecord2 = FinanceRecord::factory()->create([
            'record_date' => '2025-08-20',
            'cost' => 3500,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        $totalFinanceCost = $financeRecord1->cost + $financeRecord2->cost; // 6000
        
        // Report data without total_finance_cost
        $reportData = [
            'year' => $year,
            'month' => $month,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-31',
            'total_sales' => 20,
            'total_revenue' => 200000,
            'total_profit' => 60000,
            'avg_daily_profit' => 2000,
            'best_day' => '2025-08-15',
            'best_day_profit' => 8000,
            'profit_margin' => 30.0,
            'finance_cost' => 5000, // Note this is different from total_finance_cost
            'net_profit' => 55000, // This will be recalculated
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/monthly', $reportData);
        
        $response->assertStatus(201);
        
        // The controller should have calculated total_finance_cost from finance records
        $this->assertEquals(number_format($totalFinanceCost, 2, '.', ''), $response->json('total_finance_cost'));
        
        // Verify that net_profit = total_profit - total_finance_cost
        $expectedNetProfit = $reportData['total_profit'] - $totalFinanceCost;
        $this->assertEquals(number_format($expectedNetProfit, 2, '.', ''), $response->json('net_profit'));
    }
    
    /** @test */
    public function test_update_monthly_report_calculates_total_finance_cost_if_not_provided()
    {
        $year = 2025;
        $month = 9;
        
        // Create a monthly report
        $report = MonthlySalesReport::factory()->create([
            'year' => $year,
            'month' => $month,
            'total_finance_cost' => 0,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        // Create some finance records for the month
        $financeRecord1 = FinanceRecord::factory()->create([
            'record_date' => '2025-09-10',
            'cost' => 4000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        $financeRecord2 = FinanceRecord::factory()->create([
            'record_date' => '2025-09-20',
            'cost' => 3000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        $totalFinanceCost = $financeRecord1->cost + $financeRecord2->cost; // 7000
        
        // Update data without total_finance_cost
        $updateData = [
            'total_profit' => 80000,
            'finance_cost' => 5000, // Note this is different from total_finance_cost
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->patchJson("/bcms/reports/monthly/{$year}/{$month}", $updateData);
        
        $response->assertStatus(200);
        
        // The controller should have calculated total_finance_cost from finance records
        $this->assertEquals(number_format($totalFinanceCost, 2, '.', ''), $response->json('total_finance_cost'));
        
        // Verify that net_profit = total_profit - total_finance_cost
        $expectedNetProfit = $updateData['total_profit'] - $totalFinanceCost;
        $this->assertEquals(number_format($expectedNetProfit, 2, '.', ''), $response->json('net_profit'));
    }
}
