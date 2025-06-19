<?php

namespace Tests\Feature\Api;

use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class YearlySalesReportApiTest extends TestCase
{
    use RefreshDatabase;

    protected $managerToken = 'manager-test-token';
    protected $userToken = 'user-test-token';
    protected $manager;
    protected $user;
    protected $supabaseServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->manager = User::factory()->create([
            'email' => 'yearly-report-test-manager@example.com',
            'role' => 'Manager',
            'name' => 'Yearly Report Test Manager'
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'yearly-report-test-user@example.com', 
            'role' => 'User',
            'name' => 'Yearly Report Test User'
        ]);
        
        // Mock the SupabaseService
        $this->supabaseServiceMock = Mockery::mock(SupabaseService::class);
        $this->app->instance(SupabaseService::class, $this->supabaseServiceMock);
        
        // Setup the mock for the manager token
        $this->supabaseServiceMock->shouldReceive('getUserByAccessToken')
            ->with($this->managerToken)
            ->andReturn([
                'id' => $this->manager->id,
                'email' => $this->manager->email,
                'name' => $this->manager->name,
                'role' => 'Manager'
            ])
            ->byDefault();
            
        // Setup the mock for the user token
        $this->supabaseServiceMock->shouldReceive('getUserByAccessToken')
            ->with($this->userToken)
            ->andReturn([
                'id' => $this->user->id,
                'email' => $this->user->email,
                'name' => $this->user->name,
                'role' => 'User'
            ])
            ->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_can_get_all_yearly_reports()
    {
        // Create some yearly reports
        YearlySalesReport::factory()->create(['year' => 2023]);
        YearlySalesReport::factory()->create(['year' => 2024]);
        YearlySalesReport::factory()->create(['year' => 2025]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/yearly-reports');
        
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }
    
    public function test_can_get_single_yearly_report()
    {
        $report = YearlySalesReport::factory()->create([
            'year' => 2025,
            'total_sales' => 100,
            'total_revenue' => 1000000,
            'total_profit' => 300000
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/yearly?year=2025');
        
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'year' => 2025,
                     'total_sales' => 100,
                     'total_revenue' => '1000000.00',
                     'total_profit' => '300000.00'
                 ]);
    }
    
    public function test_404_for_nonexistent_yearly_report()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/reports/yearly?year=1999');
        
        $response->assertStatus(404)
                 ->assertJsonFragment([
                     'message' => 'No yearly report found for this year.'
                 ]);
    }
      public function test_manager_can_create_yearly_report()
    {
        $reportData = [
            'year' => 2025,
            'total_sales' => 150,
            'total_revenue' => 1500000,
            'total_profit' => 450000,
            'avg_monthly_profit' => 37500,
            'best_month' => 6,
            'best_month_profit' => 50000,
            'profit_margin' => 30,
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/yearly', $reportData);
        
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'year' => 2025,
                     'total_sales' => 150,
                     'total_revenue' => '1500000.00',
                     'total_profit' => '450000.00',
                 ]);
    }
    
    public function test_manager_can_update_yearly_report()
    {        $report = YearlySalesReport::factory()->create([
            'year' => 2025,
            'total_sales' => 100,
            'total_revenue' => 1000000,
            'total_profit' => 300000,
        ]);
        
        $updatedData = [
            'total_sales' => 120,
            'total_revenue' => 1200000,
            'total_profit' => 360000
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/reports/yearly/' . $report->year, $updatedData);
        
        $response->assertStatus(200)                 ->assertJsonFragment([
                     'total_sales' => 120,
                     'total_revenue' => '1200000.00',
                     'total_profit' => '360000.00'
                 ]);
    }
    
    public function test_manager_can_delete_yearly_report()
    {
        $report = YearlySalesReport::factory()->create([
            'year' => 2025
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/reports/yearly/' . $report->year);
        
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Yearly sales report deleted successfully.'
                 ]);
        
        $this->assertDatabaseMissing('yearlysalesreport', ['year' => 2025]);
    }
      public function test_regular_user_cannot_create_yearly_report()
    {
        $reportData = [
            'year' => 2025,
            'total_sales' => 150,
            'total_revenue' => 1500000,
            'total_profit' => 450000,
            'avg_monthly_profit' => 37500,
            'best_month' => 6,
            'best_month_profit' => 50000,
            'profit_margin' => 30
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/reports/yearly', $reportData);
        
        $response->assertStatus(403);
    }
    
    public function test_report_validation_rules()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/yearly', []);
          $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'year', 'total_sales', 'total_revenue', 'total_profit', 
                     'avg_monthly_profit'
                 ]);
    }
    
    public function test_cannot_create_duplicate_yearly_report()
    {
        // Create a report for 2025
        YearlySalesReport::factory()->create([
            'year' => 2025
        ]);
        
        // Try to create another report for 2025
        $reportData = [
            'year' => 2025,
            'total_sales' => 150,
            'total_revenue' => 1500000,
            'total_profit' => 450000,            'avg_monthly_profit' => 37500,
            'best_month' => 6,
            'best_month_profit' => 50000,
            'profit_margin' => 30
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/yearly', $reportData);
        
        $response->assertStatus(422)
                 ->assertJsonFragment([
                     'message' => 'A yearly report already exists for this year.'
                 ]);
    }
    
    public function test_manager_can_generate_yearly_report_from_monthly_data()
    {
        // Setup monthly data for 2025
        MonthlySalesReport::create([
            'year' => 2025,
            'month' => 1,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'total_sales' => 10,
            'total_revenue' => 100000,
            'total_profit' => 30000,
            'avg_daily_profit' => 1000,
            'finance_cost' => 5000,
            'total_finance_cost' => 5000,
            'net_profit' => 25000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        MonthlySalesReport::create([
            'year' => 2025,
            'month' => 2,
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'total_sales' => 15,
            'total_revenue' => 150000,
            'total_profit' => 45000,
            'avg_daily_profit' => 1500,
            'finance_cost' => 7500,
            'total_finance_cost' => 7500,
            'net_profit' => 37500,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/yearly/generate', ['year' => 2025]);
        
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'message' => 'Yearly sales report generated successfully for 2025'
                 ]);
          // Check that the report was generated with correct totals
        $this->assertDatabaseHas('yearlysalesreport', [
            'year' => 2025,
            'total_sales' => 25,
            'total_revenue' => 250000.00,
            'total_profit' => 75000.00,
        ]);
    }
    
    public function test_cannot_generate_report_without_monthly_data()
    {
        // No monthly data for 2026
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/reports/yearly/generate', ['year' => 2026]);
        
        $response->assertStatus(422)
                 ->assertJsonFragment([
                     'message' => 'No monthly reports found for the year 2026. Cannot generate yearly report.'
                 ]);
    }
}
