<?php

namespace Tests\Feature\Api;

use App\Models\DailySalesReport;
use App\Models\User;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class DailySalesReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected $managerToken;
    protected $userToken;
    protected $manager;
    protected $user;
    protected $car;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->manager = User::factory()->create([
            'email' => 'report-test-manager@example.com',
            'role' => 'Manager',
            'name' => 'Report Test Manager'
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'report-test-user@example.com', 
            'role' => 'User',
            'name' => 'Report Test User'
        ]);
        
        // Create a car for testing
        $this->car = Car::factory()->create();
        
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
     * Test that a manager can list all daily sales reports.
     *
     * @return void
     */
    public function test_manager_can_list_daily_sales_reports()
    {
        // Create multiple test reports
        DailySalesReport::factory()->forDate('2025-06-01')->create();
        DailySalesReport::factory()->forDate('2025-06-02')->create();
        DailySalesReport::factory()->forDate('2025-06-03')->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily/list');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }
    
    /**
     * Test that a manager can retrieve a daily sales report.
     *
     * @return void
     */
    public function test_manager_can_retrieve_daily_sales_report()
    {
        // Create a test report
        $reportDate = '2025-06-01';
        $report = DailySalesReport::factory()->forDate($reportDate)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily?date=' . $reportDate);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'report_date' => $reportDate,
                     'total_sales' => $report->total_sales,
                     'total_revenue' => (string)$report->total_revenue,
                     'total_profit' => (string)$report->total_profit
                 ]);
    }
    
    /**
     * Test that manual daily report creation is not available (reports are auto-generated).
     *
     * @return void
     */
    public function test_manual_daily_report_creation_is_not_available()
    {
        $this->actingAs($this->manager);
        
        $reportData = [
            'report_date' => '2025-06-10',
            'total_sales' => 5,
            'total_revenue' => 50000,
            'total_profit' => 15000,
            'avg_profit_per_sale' => 3000,
            'most_profitable_car_id' => $this->car->id,
            'highest_single_profit' => 5000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/daily', $reportData);

        $response->assertStatus(405); // Method Not Allowed
        
        // Note: Daily reports are now generated automatically via scheduled tasks
    }
    
    /**
     * Test that a manager can update a daily sales report.
     *
     * @return void
     */
    public function test_manager_can_update_daily_sales_report()
    {
        $this->actingAs($this->manager);
        
        // Create a test report
        $reportDate = '2025-06-15';
        DailySalesReport::factory()->forDate($reportDate)->create([
            'total_sales' => 3,
            'total_revenue' => 30000,
            'total_profit' => 9000,
            'avg_profit_per_sale' => 3000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);
        
        $updateData = [
            'total_sales' => 4,
            'total_revenue' => 40000,
            'total_profit' => 12000,
            'avg_profit_per_sale' => 3000
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/reports/daily/' . $reportDate, $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'report_date' => $reportDate,
                     'total_sales' => 4,
                     'total_revenue' => '40000.00',
                     'total_profit' => '12000.00'
                 ]);
        
        $this->assertDatabaseHas('dailysalesreport', [
            'report_date' => $reportDate,
            'total_sales' => 4,
            'updated_by' => $this->manager->id
        ]);
    }
    
    /**
     * Test that a manager can delete a daily sales report.
     *
     * @return void
     */
    public function test_manager_can_delete_daily_sales_report()
    {
        $this->actingAs($this->manager);
        
        // Create a test report
        $reportDate = '2025-06-20';
        DailySalesReport::factory()->forDate($reportDate)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/reports/daily/' . $reportDate);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Daily sales report deleted successfully.'
                 ]);
        
        $this->assertDatabaseMissing('dailysalesreport', [
            'report_date' => $reportDate
        ]);
    }
    
    /**
     * Test that a manager receives a 404 when requesting a non-existent report.
     *
     * @return void
     */
    public function test_manager_gets_404_for_nonexistent_report()
    {
        $nonExistentDate = '2099-12-31'; // A date far in the future

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily?date=' . $nonExistentDate);

        $response->assertStatus(404)
                 ->assertJsonFragment([
                     'message' => 'No daily report found for this date.'
                 ]);
    }
    
    /**
     * Test that a regular user cannot access daily sales reports.
     *
     * @return void
     */
    public function test_regular_user_cannot_access_daily_sales_reports()
    {
        // Create a test report
        $reportDate = '2025-06-02';
        DailySalesReport::factory()->forDate($reportDate)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Test GET (show)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/reports/daily?date=' . $reportDate);
        $response->assertStatus(403); // Forbidden
        
        // Test GET (index)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/reports/daily/list');
        $response->assertStatus(403);
        
        // Test POST (create)
        $reportData = [
            'report_date' => '2025-06-25',
            'total_sales' => 5,
            'total_revenue' => 50000,
            'total_profit' => 15000,
            'avg_profit_per_sale' => 3000
        ];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/reports/daily', $reportData);
        $response->assertStatus(405); // Method Not Allowed - POST endpoint removed
        
        // Test PUT (update)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/reports/daily/' . $reportDate, ['total_sales' => 10]);
        $response->assertStatus(403);
        
        // Test DELETE
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/reports/daily/' . $reportDate);
        $response->assertStatus(403);
    }
    
    /**
     * Test that an unauthenticated user cannot access daily sales reports.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_daily_sales_reports()
    {
        $reportDate = '2025-06-03';
        DailySalesReport::factory()->forDate($reportDate)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Test GET (show)
        $response = $this->getJson('/bcms/reports/daily?date=' . $reportDate);
        $response->assertStatus(401); // Unauthorized
        
        // Test GET (index)
        $response = $this->getJson('/bcms/reports/daily/list');
        $response->assertStatus(401);
        
        // Test POST (create)
        $reportData = [
            'report_date' => '2025-06-26',
            'total_sales' => 5,
            'total_revenue' => 50000,
            'total_profit' => 15000,
            'avg_profit_per_sale' => 3000
        ];
        $response = $this->postJson('/bcms/reports/daily', $reportData);
        $response->assertStatus(405); // Method Not Allowed - POST endpoint removed
        
        // Test PUT (update)
        $response = $this->putJson('/bcms/reports/daily/' . $reportDate, ['total_sales' => 10]);
        $response->assertStatus(401);
        
        // Test DELETE
        $response = $this->deleteJson('/bcms/reports/daily/' . $reportDate);
        $response->assertStatus(401);
    }
    
    /**
     * Test validation rules for creating daily sales reports.
     *
     * @return void
     */
    public function test_create_daily_report_validation_rules()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/daily', []);

        $response->assertStatus(405); // Method Not Allowed - POST endpoint removed
        
        // Note: Manual report creation is no longer supported
        // Reports are generated automatically via scheduled tasks
    }
    
    /**
     * Test validation rules for the daily report show endpoint.
     *
     * @return void
     */
    public function test_show_daily_report_validation_rules()
    {
        // Test missing date parameter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
        
        // Test invalid date format
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily?date=invalid-date');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }
    
    /**
     * Test that the daily report contains accurate calculations.
     *
     * @return void
     */
    public function test_daily_report_calculations_are_accurate()
    {
        $reportDate = '2025-06-04';
        $totalSales = 5;
        $totalRevenue = 50000;
        $totalProfit = 15000;
        $avgProfitPerSale = $totalProfit / $totalSales;
        
        // Create a report with specific values
        DailySalesReport::factory()->forDate($reportDate)->create([
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'avg_profit_per_sale' => $avgProfitPerSale
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily?date=' . $reportDate);

        $response->assertStatus(200);
          // Verify calculation accuracy
        $this->assertEquals($totalSales, $response->json('total_sales'));
        $this->assertEquals(number_format($totalRevenue, 2, '.', ''), $response->json('total_revenue'));
        $this->assertEquals(number_format($totalProfit, 2, '.', ''), $response->json('total_profit'));
        $this->assertEquals(number_format($avgProfitPerSale, 2, '.', ''), $response->json('avg_profit_per_sale'));
    }
    
    /**
     * Test that date filtering works for the index endpoint.
     *
     * @return void
     */
    public function test_daily_report_date_filtering()
    {
        // Create multiple reports with different dates
        DailySalesReport::factory()->forDate('2025-05-01')->create();
        DailySalesReport::factory()->forDate('2025-06-01')->create();
        DailySalesReport::factory()->forDate('2025-06-15')->create();
        DailySalesReport::factory()->forDate('2025-07-01')->create();
        
        // Test filtering by from_date
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily/list?from_date=2025-06-01');
        
        $response->assertStatus(200);
        $this->assertCount(3, $response->json()); // Should return 3 reports (June 1, June 15, July 1)
        
        // Test filtering by to_date
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily/list?to_date=2025-06-15');
        
        $response->assertStatus(200);
        $this->assertCount(3, $response->json()); // Should return 3 reports (May 1, June 1, June 15)
        
        // Test filtering by date range
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/daily/list?from_date=2025-06-01&to_date=2025-06-30');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json()); // Should return 2 reports (June 1, June 15)
    }
}
