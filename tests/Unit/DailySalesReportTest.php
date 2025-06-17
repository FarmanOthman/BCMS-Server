<?php

declare(strict_types=1);

/**
 * Unit Test: Daily Sales Report Model
 * 
 * Purpose:
 * This test focuses on the DailySalesReport model in isolation, testing its 
 * basic CRUD operations, relationships, and business rules.
 * 
 * What it tests:
 * - Creating new DailySalesReport instances
 * - Verifying fillable attributes
 * - Testing model relationships (mostProfitableCar, createdBy, updatedBy)
 * - Updating existing reports
 * - Finding reports by date range
 * - Calculating averages and totals across reports
 * 
 * Usage:
 * Run this test when making changes to the DailySalesReport model to ensure
 * its basic functionality and relationships continue to work as expected.
 * This test is more focused on the model itself rather than the end-to-end process.
 * 
 * Note:
 * This test includes 60-second pauses to allow for manual database inspection.
 */

namespace Tests\Unit;

use App\Models\DailySalesReport;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DailySalesReportTest extends TestCase
{
    protected $user;
    protected $make;
    protected $model;
    protected $car;    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing reports from previous test runs
        $this->cleanupExistingReports();
        
        // Create a test user for all tests
        $this->user = new User();
        $this->user->id = Str::uuid(); // Explicitly set a UUID
        $this->user->name = 'Test User';
        $this->user->email = 'test_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();
        
        // Create a test make
        $this->make = new Make();
        $this->make->id = Str::uuid(); // Explicitly set a UUID
        $this->make->name = 'Test Make';
        $this->make->save();
        
        // Create a test model
        $this->model = new Model();
        $this->model->id = Str::uuid(); // Explicitly set a UUID
        $this->model->name = 'Test Model';
        $this->model->make_id = $this->make->id;
        $this->model->save();
        
        // Create a test car (will be used as most_profitable_car_id)
        $this->car = new Car();
        $this->car->id = Str::uuid(); // Explicitly set a UUID
        $this->car->make_id = $this->make->id;
        $this->car->model_id = $this->model->id;
        $this->car->year = 2025;
        $this->car->vin = 'TEST' . rand(10000, 99999);
        $this->car->cost_price = 20000;
        $this->car->transition_cost = 500;
        $this->car->total_repair_cost = 1000;
        $this->car->public_price = 25000;
        $this->car->status = 'available';
        $this->car->created_by = $this->user->id;
        $this->car->updated_by = $this->user->id;
        $this->car->save();
    }
    
    protected function tearDown(): void
    {
        // Clean up all created resources in the reverse order
        if ($this->car) {
            $this->car->delete();
        }
        
        if ($this->model) {
            $this->model->delete();
        }
        
        if ($this->make) {
            $this->make->delete();
        }
        
        if ($this->user) {
            $this->user->delete();
        }
        
        parent::tearDown();
    }    public function test_can_create_daily_sales_report()
    {
        $reportDate = Carbon::today();
        $reportDateString = $reportDate->toDateString();
        
        $report = new DailySalesReport();
        $report->report_date = $reportDate;
        $report->total_sales = 5;
        $report->total_revenue = 100000.00;
        $report->total_profit = 20000.00;
        $report->avg_profit_per_sale = 4000.00;
        $report->most_profitable_car_id = $this->car->id;
        $report->highest_single_profit = 8000.00;
        $report->created_by = $this->user->id;
        $report->updated_by = $this->user->id;
        $report->save();

        $this->assertInstanceOf(DailySalesReport::class, $report);
        $this->assertEquals($reportDate->format('Y-m-d'), $report->report_date->format('Y-m-d'));
        $this->assertEquals(5, $report->total_sales);
        $this->assertEquals(100000.00, $report->total_revenue);
        $this->assertEquals(20000.00, $report->total_profit);
        $this->assertEquals(4000.00, $report->avg_profit_per_sale);
        $this->assertEquals($this->car->id, $report->most_profitable_car_id);
        $this->assertEquals(8000.00, $report->highest_single_profit);
        
        // Pause for 60 seconds so you can see the record in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** The record with report_date = {$reportDate} should be visible ***\n";
        sleep(60);
        
        // Clean up
        $report->delete();
    }

    public function test_fillable_attributes()
    {
        $report = new DailySalesReport();
        
        $fillable = $report->getFillable();
        
        $this->assertContains('report_date', $fillable);
        $this->assertContains('total_sales', $fillable);
        $this->assertContains('total_revenue', $fillable);
        $this->assertContains('total_profit', $fillable);
        $this->assertContains('avg_profit_per_sale', $fillable);
        $this->assertContains('most_profitable_car_id', $fillable);
        $this->assertContains('highest_single_profit', $fillable);
        $this->assertContains('created_by', $fillable);
        $this->assertContains('updated_by', $fillable);
    }    public function test_has_relationships()
    {
        $reportDate = Carbon::today()->subDays(1);
        $reportDateString = $reportDate->toDateString();
        
        $report = new DailySalesReport();
        $report->report_date = $reportDate;
        $report->total_sales = 3;
        $report->total_revenue = 75000.00;
        $report->total_profit = 15000.00;
        $report->avg_profit_per_sale = 5000.00;
        $report->most_profitable_car_id = $this->car->id;
        $report->highest_single_profit = 7500.00;
        $report->created_by = $this->user->id;
        $report->updated_by = $this->user->id;
        $report->save();

        // Test relationship with Car
        $this->assertInstanceOf(Car::class, $report->mostProfitableCar);
        $this->assertEquals($this->car->id, $report->mostProfitableCar->id);
        $this->assertEquals($this->car->vin, $report->mostProfitableCar->vin);
        
        // Test relationship with User (created_by)
        $this->assertInstanceOf(User::class, $report->createdBy);
        $this->assertEquals($this->user->id, $report->createdBy->id);
        $this->assertEquals($this->user->name, $report->createdBy->name);
        
        // Test relationship with User (updated_by)
        $this->assertInstanceOf(User::class, $report->updatedBy);
        $this->assertEquals($this->user->id, $report->updatedBy->id);
        $this->assertEquals($this->user->name, $report->updatedBy->name);
        
        // Pause for 60 seconds so you can see the record in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** The record with report_date = {$reportDate} should be visible ***\n";
        sleep(60);
        
        // Clean up
        $report->delete();
    }    public function test_update_daily_sales_report()
    {
        $reportDate = Carbon::today()->subDays(2);
        $reportDateString = $reportDate->toDateString();
        
        $report = new DailySalesReport();
        $report->report_date = $reportDate;
        $report->total_sales = 2;
        $report->total_revenue = 50000.00;
        $report->total_profit = 10000.00;
        $report->avg_profit_per_sale = 5000.00;
        $report->created_by = $this->user->id;
        $report->updated_by = $this->user->id;
        $report->save();

        // Update the report with end-of-day sales
        $report->total_sales = 4;
        $report->total_revenue = 90000.00;
        $report->total_profit = 18000.00;
        $report->avg_profit_per_sale = 4500.00;
        $report->most_profitable_car_id = $this->car->id;
        $report->highest_single_profit = 6000.00;
        $report->save();
        
        // Refresh from database
        $updatedReport = DailySalesReport::find($reportDateString);
        
        $this->assertEquals(4, $updatedReport->total_sales);
        $this->assertEquals(90000.00, $updatedReport->total_revenue);
        $this->assertEquals(18000.00, $updatedReport->total_profit);
        $this->assertEquals(4500.00, $updatedReport->avg_profit_per_sale);
        $this->assertEquals($this->car->id, $updatedReport->most_profitable_car_id);
        $this->assertEquals(6000.00, $updatedReport->highest_single_profit);
        
        // Pause for 60 seconds so you can see the record in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** The record with report_date = {$reportDate} should be visible ***\n";
        sleep(60);
        
        // Clean up
        $report->delete();
    }    public function test_find_reports_by_date_range()
    {
        // Create reports for multiple days
        $yesterday = Carbon::yesterday();
        $yesterdayString = $yesterday->toDateString();
        
        $twoDaysAgo = Carbon::yesterday()->subDays(1);
        $twoDaysAgoString = $twoDaysAgo->toDateString();
        
        $threeDaysAgo = Carbon::yesterday()->subDays(2);
        $threeDaysAgoString = $threeDaysAgo->toDateString();
        
        $report1 = new DailySalesReport();
        $report1->report_date = $yesterday;
        $report1->total_sales = 5;
        $report1->total_revenue = 100000.00;
        $report1->total_profit = 20000.00;
        $report1->avg_profit_per_sale = 4000.00;
        $report1->created_by = $this->user->id;
        $report1->updated_by = $this->user->id;
        $report1->save();
        
        $report2 = new DailySalesReport();
        $report2->report_date = $twoDaysAgo;
        $report2->total_sales = 3;
        $report2->total_revenue = 75000.00;
        $report2->total_profit = 15000.00;
        $report2->avg_profit_per_sale = 5000.00;
        $report2->created_by = $this->user->id;
        $report2->updated_by = $this->user->id;
        $report2->save();
        
        $report3 = new DailySalesReport();
        $report3->report_date = $threeDaysAgo;
        $report3->total_sales = 2;
        $report3->total_revenue = 40000.00;
        $report3->total_profit = 8000.00;
        $report3->avg_profit_per_sale = 4000.00;
        $report3->created_by = $this->user->id;
        $report3->updated_by = $this->user->id;
        $report3->save();
          // Test finding by specific date
        $yesterdayReport = DailySalesReport::find($yesterdayString);
        $this->assertNotNull($yesterdayReport);
        $this->assertEquals(5, $yesterdayReport->total_sales);
        
        // Test finding by date range
        $lastTwoDaysReports = DailySalesReport::where('report_date', '>=', $twoDaysAgoString)
                                           ->where('report_date', '<=', $yesterdayString)
                                           ->orderBy('report_date')
                                           ->get();
        $this->assertEquals(2, $lastTwoDaysReports->count());
        $this->assertEquals($twoDaysAgoString, $lastTwoDaysReports[0]->report_date->toDateString());
        $this->assertEquals($yesterdayString, $lastTwoDaysReports[1]->report_date->toDateString());
          // Test aggregating data over a date range
        $totalSalesLastThreeDays = DailySalesReport::where('report_date', '>=', $threeDaysAgoString)
                                                ->sum('total_sales');
        $this->assertEquals(10, $totalSalesLastThreeDays); // 5 + 3 + 2
        
        $totalRevenueLastThreeDays = DailySalesReport::where('report_date', '>=', $threeDaysAgoString)
                                                  ->sum('total_revenue');
        $this->assertEquals(215000.00, $totalRevenueLastThreeDays); // 100000 + 75000 + 40000
        
        $totalProfitLastThreeDays = DailySalesReport::where('report_date', '>=', $threeDaysAgoString)
                                                 ->sum('total_profit');
        $this->assertEquals(43000.00, $totalProfitLastThreeDays); // 20000 + 15000 + 8000
          // Pause for 60 seconds so you can see the records in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** There should be 3 records for the dates: {$yesterdayString}, {$twoDaysAgoString}, {$threeDaysAgoString} ***\n";
        sleep(60);
        
        // Clean up
        $report1->delete();
        $report2->delete();
        $report3->delete();
    }    public function test_calculate_averages_and_totals()
    {
        // Create reports for a week
        $reports = [];
        $totalSales = 0;
        $totalRevenue = 0;
        $totalProfit = 0;
        
        for ($i = 0; $i < 7; $i++) {
            $reportDate = Carbon::today()->subDays($i);
            $reportDateString = $reportDate->toDateString();
            
            $sales = rand(1, 5);
            $revenue = $sales * 20000 + rand(0, 10000);
            $profit = $revenue * 0.2 + rand(0, 1000);
            
            $report = new DailySalesReport();
            $report->report_date = $reportDate;
            $report->total_sales = $sales;
            $report->total_revenue = $revenue;
            $report->total_profit = $profit;
            $report->avg_profit_per_sale = $sales > 0 ? $profit / $sales : 0;
            $report->created_by = $this->user->id;
            $report->updated_by = $this->user->id;
            $report->save();
            
            $totalSales += $sales;
            $totalRevenue += $revenue;
            $totalProfit += $profit;
            
            $reports[] = $report;
        }
        
        // Calculate weekly totals from the database
        $weekAgo = Carbon::today()->subDays(6);
        $weekAgoString = $weekAgo->toDateString();
        $today = Carbon::today();
        $todayString = $today->toDateString();
          $dbTotalSales = DailySalesReport::where('report_date', '>=', $weekAgoString)
                                      ->where('report_date', '<=', $todayString)
                                      ->sum('total_sales');
        
        $dbTotalRevenue = DailySalesReport::where('report_date', '>=', $weekAgoString)
                                        ->where('report_date', '<=', $todayString)
                                        ->sum('total_revenue');
        
        $dbTotalProfit = DailySalesReport::where('report_date', '>=', $weekAgoString)
                                       ->where('report_date', '<=', $todayString)
                                       ->sum('total_profit');
        
        // Calculate weekly average daily sales
        $dbAvgDailySales = DailySalesReport::where('report_date', '>=', $weekAgoString)
                                         ->where('report_date', '<=', $todayString)
                                         ->avg('total_sales');
        
        // Test that our calculated totals match the database aggregates
        $this->assertEquals($totalSales, $dbTotalSales);
        $this->assertEquals($totalRevenue, $dbTotalRevenue);
        $this->assertEquals($totalProfit, $dbTotalProfit);
        $this->assertEquals($totalSales / 7, $dbAvgDailySales);
        
        // Pause for 60 seconds so you can see the records in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** There should be 7 records for the last week ***\n";
        sleep(60);
        
        // Clean up
        foreach ($reports as $report) {
            $report->delete();
        }
    }
    
    // Helper method to clean up existing reports
    private function cleanupExistingReports()
    {
        // Get dates for the last week to clean up any reports
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = Carbon::today()->subDays($i)->format('Y-m-d');
        }
        
        // Delete existing reports for these dates
        foreach ($dates as $dateStr) {
            $existingReport = DailySalesReport::find($dateStr);
            if ($existingReport) {
                echo "Cleaning up existing report for {$dateStr}\n";
                $existingReport->delete();
            }
        }
    }
}
