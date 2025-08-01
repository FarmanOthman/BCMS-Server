<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Models\Car;
use App\Models\Buyer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckMissingReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Car $car;
    protected Buyer $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create(['role' => 'Manager']);
        
        // Create test car and buyer
        $this->car = Car::factory()->create(['status' => 'available']);
        $this->buyer = Buyer::factory()->create();
    }

    public function test_check_missing_reports_command_with_no_sales()
    {
        $this->artisan('reports:check-missing')
            ->expectsOutput('No sales found in the specified date range.')
            ->assertExitCode(0);
    }

    public function test_check_missing_reports_command_with_sales()
    {
        // Create a sale
        $saleDate = '2024-01-15';
        Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_date' => $saleDate,
            'sale_price' => 25000,
            'purchase_cost' => 20000,
            'profit_loss' => 5000,
        ]);

        $this->artisan('reports:check-missing', ['--from' => '2024-01-01', '--to' => '2024-01-31'])
            ->expectsOutput('Found 1 unique sale dates with sales data.')
            ->expectsOutput('Processing date: 2024-01-15')
            ->expectsOutput('✓ Generated reports for 2024-01-15')
            ->expectsOutput('All reports processed successfully!')
            ->assertExitCode(0);

        // Verify reports were created
        $this->assertTrue(DailySalesReport::where('report_date', $saleDate)->exists());
        $this->assertTrue(MonthlySalesReport::where('year', 2024)->where('month', 1)->exists());
        $this->assertTrue(YearlySalesReport::where('year', 2024)->exists());
    }

    public function test_check_missing_reports_dry_run()
    {
        // Create a sale
        $saleDate = '2024-01-15';
        Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_date' => $saleDate,
            'sale_price' => 25000,
            'purchase_cost' => 20000,
            'profit_loss' => 5000,
        ]);

        $this->artisan('reports:check-missing', [
            '--from' => '2024-01-01', 
            '--to' => '2024-01-31',
            '--dry-run' => true
        ])
            ->expectsOutput('DRY RUN MODE - No reports will be actually generated')
            ->expectsOutput('Found 1 unique sale dates with sales data.')
            ->expectsOutput('Processing date: 2024-01-15')
            ->expectsOutput('✓ Would generate reports for 2024-01-15')
            ->assertExitCode(0);

        // Verify no reports were actually created
        $this->assertFalse(DailySalesReport::where('report_date', $saleDate)->exists());
        $this->assertFalse(MonthlySalesReport::where('year', 2024)->where('month', 1)->exists());
        $this->assertFalse(YearlySalesReport::where('year', 2024)->exists());
    }

    public function test_check_missing_reports_with_multiple_sales()
    {
        // Create sales on different dates
        $sale1 = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_date' => '2024-01-15',
            'sale_price' => 25000,
            'purchase_cost' => 20000,
            'profit_loss' => 5000,
        ]);

        $car2 = Car::factory()->create(['status' => 'available']);
        $sale2 = Sale::factory()->create([
            'car_id' => $car2->id,
            'buyer_id' => $this->buyer->id,
            'sale_date' => '2024-01-20',
            'sale_price' => 30000,
            'purchase_cost' => 25000,
            'profit_loss' => 5000,
        ]);

        $this->artisan('reports:check-missing', ['--from' => '2024-01-01', '--to' => '2024-01-31'])
            ->expectsOutput('Found 2 unique sale dates with sales data.')
            ->expectsOutput('Processing date: 2024-01-15')
            ->expectsOutput('✓ Generated reports for 2024-01-15')
            ->expectsOutput('Processing date: 2024-01-20')
            ->expectsOutput('✓ Generated reports for 2024-01-20')
            ->expectsOutput('All reports processed successfully!')
            ->assertExitCode(0);

        // Verify reports were created for both dates
        $this->assertTrue(DailySalesReport::where('report_date', '2024-01-15')->exists());
        $this->assertTrue(DailySalesReport::where('report_date', '2024-01-20')->exists());
        
        // Monthly and yearly reports should exist for 2024
        $this->assertTrue(MonthlySalesReport::where('year', 2024)->where('month', 1)->exists());
        $this->assertTrue(YearlySalesReport::where('year', 2024)->exists());
    }
} 