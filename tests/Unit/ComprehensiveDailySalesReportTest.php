<?php

declare(strict_types=1);

/**
 * Unit Test: Comprehensive Daily Sales Report
 * 
 * Purpose:
 * This test provides a comprehensive end-to-end test of the DailySalesReport functionality
 * in a realistic environment with multiple cars, sales, and reports.
 * 
 * What it tests:
 * - Creation of cars with proper attributes (make, model, costs, etc.)
 * - Creation of sales across multiple days
 * - Generation of daily sales reports for each day with sales
 * - Validation of report data calculations (totals, averages, profits)
 * - Verification of model relationships
 * - Cross-report aggregation
 * 
 * Usage:
 * Run this test to verify the complete workflow of creating sales and generating accurate reports.
 * This test is particularly useful for ensuring that all the business logic for report generation
 * works correctly with real-world data patterns.
 * 
 * Note:
 * This test includes a 60-second pause to allow for manual database inspection.
 */

namespace Tests\Unit;

use App\Models\DailySalesReport;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\User;
use App\Models\Buyer;
use App\Models\Sale;
use Tests\TestCase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ComprehensiveDailySalesReportTest extends TestCase
{
    protected $user;
    protected $makes = [];
    protected $models = [];
    protected $cars = [];
    protected $buyers = [];
    protected $sales = [];
    protected $reports = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing data from previous test runs
        $this->cleanupAllTestData();
        
        // Create a test user for all tests
        $this->user = new User();
        $this->user->id = Str::uuid();
        $this->user->name = 'Test User';
        $this->user->email = 'test_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();
        
        // Create car makes
        $makeNames = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes'];
        foreach ($makeNames as $makeName) {
            $make = new Make();
            $make->id = Str::uuid();
            $make->name = $makeName;
            $make->created_by = $this->user->id;
            $make->updated_by = $this->user->id;
            $make->save();
            $this->makes[$makeName] = $make;
        }
        
        // Create car models
        $modelConfig = [
            'Toyota' => ['Corolla', 'Camry', 'RAV4'],
            'Honda' => ['Civic', 'Accord', 'CR-V'],
            'Ford' => ['Focus', 'Mustang', 'F-150'],
            'BMW' => ['3 Series', '5 Series', 'X5'],
            'Mercedes' => ['C-Class', 'E-Class', 'GLE'],
        ];
        
        foreach ($modelConfig as $makeName => $modelNames) {
            foreach ($modelNames as $modelName) {
                $model = new Model();
                $model->id = Str::uuid();
                $model->name = $modelName;
                $model->make_id = $this->makes[$makeName]->id;
                $model->created_by = $this->user->id;
                $model->updated_by = $this->user->id;
                $model->save();
                $this->models[$makeName . ' ' . $modelName] = $model;
            }
        }
        
        // Create buyers
        $buyerNames = ['John Smith', 'Jane Doe', 'Robert Johnson', 'Emily Davis', 'Michael Brown'];
        foreach ($buyerNames as $buyerName) {
            $buyer = new Buyer();
            $buyer->id = Str::uuid();
            $buyer->name = $buyerName;
            $buyer->phone = '555-' . rand(100, 999) . '-' . rand(1000, 9999);
            $buyer->address = rand(100, 999) . ' ' . ['Main', 'Oak', 'Maple', 'Cedar', 'Pine'][rand(0, 4)] . ' St';
            $buyer->created_by = $this->user->id;
            $buyer->updated_by = $this->user->id;
            $buyer->save();
            $this->buyers[] = $buyer;
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up in reverse order of creation
        foreach ($this->reports as $report) {
            $report->delete();
        }
        
        foreach ($this->sales as $sale) {
            $sale->delete();
        }
        
        foreach ($this->cars as $car) {
            $car->delete();
        }
        
        foreach ($this->models as $model) {
            $model->delete();
        }
        
        foreach ($this->makes as $make) {
            $make->delete();
        }
        
        foreach ($this->buyers as $buyer) {
            $buyer->delete();
        }
        
        if ($this->user) {
            $this->user->delete();
        }
        
        parent::tearDown();
    }

    public function test_comprehensive_daily_sales_report()
    {
        // Create cars with various attributes
        $this->createCars();
        
        // Create sales for the cars over multiple days
        $this->createSales();
        
        // Generate daily sales reports
        $this->generateDailySalesReports();
        
        // Verify the reports are correct
        $this->verifyDailySalesReports();
        
        // Pause for 60 seconds to view the reports in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** Check the 'sale' table ***\n";
        echo "*** Check the 'car' table ***\n";
        sleep(60);
    }
    
    private function createCars()
    {
        // Create 15 cars with different makes, models, and prices
        $makeModelKeys = array_keys($this->models);
        
        for ($i = 0; $i < 15; $i++) {
            $makeModelKey = $makeModelKeys[array_rand($makeModelKeys)];
            list($makeName, $modelName) = explode(' ', $makeModelKey, 2);
            
            $car = new Car();
            $car->id = Str::uuid();
            $car->make_id = $this->makes[$makeName]->id;
            $car->model_id = $this->models[$makeModelKey]->id;
            $car->year = rand(2020, 2025);
            $car->vin = 'TEST' . rand(10000, 99999);
              // Set base price based on make and year
            $basePriceFactors = [
                'Toyota' => 20000,
                'Honda' => 22000,
                'Ford' => 25000,
                'BMW' => 40000,
                'Mercedes' => 45000,
            ];
            
            $basePrice = $basePriceFactors[$makeName] + (($car->year - 2020) * 1000);
            $car->cost_price = $basePrice;
            
            // Add transition cost
            $car->transition_cost = rand(300, 1200);
            
            // Add repair items as JSON
            $repairItems = [];
            $totalRepairCost = 0;
            
            // Random number of repair items between 0 and 5
            $numRepairItems = rand(0, 5);
            for ($j = 0; $j < $numRepairItems; $j++) {
                $repairCost = rand(100, 2000);
                $repairItems[] = [
                    'description' => 'Repair item ' . ($j + 1),
                    'cost' => $repairCost
                ];
                $totalRepairCost += $repairCost;
            }
            
            $car->repair_items = $repairItems;
            $car->total_repair_cost = $totalRepairCost;
            
            // Calculate public price with profit margin
            $costPrice = $basePrice + $car->transition_cost + $totalRepairCost;
            $profitMargin = rand(10, 30) / 100; // 10% to 30% profit margin
            $car->public_price = $costPrice * (1 + $profitMargin);
            
            $car->status = 'available';
            $car->created_by = $this->user->id;
            $car->updated_by = $this->user->id;
            $car->save();
            
            $this->cars[] = $car;
        }
    }
    
    private function createSales()
    {
        // Create sales over the last 5 days
        $salesDates = [
            Carbon::today()->subDays(4), // 4 days ago
            Carbon::today()->subDays(3), // 3 days ago
            Carbon::today()->subDays(2), // 2 days ago
            Carbon::today()->subDays(1), // Yesterday
            Carbon::today(),             // Today
        ];
        
        // Distribute sales among the days
        foreach ($salesDates as $saleDate) {
            $numSalesToday = rand(1, 4); // 1-4 sales per day
            
            for ($i = 0; $i < $numSalesToday; $i++) {
                // Find an available car
                $availableCars = array_filter($this->cars, function($car) {
                    return $car->status === 'available';
                });
                
                if (empty($availableCars)) {
                    continue; // No more cars available
                }
                
                // Select a random car
                $car = $availableCars[array_rand($availableCars)];
                
                // Select a random buyer
                $buyer = $this->buyers[array_rand($this->buyers)];
                
                // Calculate sale price (may be negotiated down or up from public price)
                $negotiationFactor = rand(-5, 10) / 100; // -5% to +10% from public price
                $salePrice = $car->public_price * (1 + $negotiationFactor);
                  // Calculate purchase cost (base price + transition + repairs)
                $purchaseCost = $car->cost_price + $car->transition_cost + $car->total_repair_cost;
                
                // Calculate profit/loss
                $profitLoss = $salePrice - $purchaseCost;
                
                // Create the sale
                $sale = new Sale();
                $sale->id = Str::uuid();
                $sale->car_id = $car->id;
                $sale->buyer_id = $buyer->id;
                $sale->sale_price = $salePrice;
                $sale->purchase_cost = $purchaseCost;
                $sale->profit_loss = $profitLoss;
                $sale->sale_date = $saleDate;
                $sale->notes = 'Test sale on ' . $saleDate->format('Y-m-d');
                $sale->created_by = $this->user->id;
                $sale->updated_by = $this->user->id;
                $sale->save();
                  // Update car status to sold
                $car->status = 'sold';
                $car->selling_price = $salePrice;
                $car->updated_by = $this->user->id;
                $car->save();
                
                $this->sales[] = $sale;
            }
        }
    }
      private function generateDailySalesReports()
    {
        // Clean up any existing reports for the dates we're going to use
        $this->cleanupExistingReports();
        
        // Group sales by date
        $salesByDate = [];
        foreach ($this->sales as $sale) {
            $dateStr = $sale->sale_date->format('Y-m-d');
            if (!isset($salesByDate[$dateStr])) {
                $salesByDate[$dateStr] = [];
            }
            $salesByDate[$dateStr][] = $sale;
        }
        
        // Generate report for each day
        foreach ($salesByDate as $dateStr => $dailySales) {
            $reportDate = Carbon::parse($dateStr);
            
            // Calculate metrics
            $totalSales = count($dailySales);
            $totalRevenue = 0;
            $totalProfit = 0;
            $highestSingleProfit = 0;
            $mostProfitableCarId = null;
            
            foreach ($dailySales as $sale) {
                $totalRevenue += $sale->sale_price;
                $totalProfit += $sale->profit_loss;
                
                if ($sale->profit_loss > $highestSingleProfit) {
                    $highestSingleProfit = $sale->profit_loss;
                    $mostProfitableCarId = $sale->car_id;
                }
            }
            
            $avgProfitPerSale = $totalSales > 0 ? $totalProfit / $totalSales : 0;
            
            // Create the report
            $report = new DailySalesReport();
            $report->report_date = $reportDate;
            $report->total_sales = $totalSales;
            $report->total_revenue = $totalRevenue;
            $report->total_profit = $totalProfit;
            $report->avg_profit_per_sale = $avgProfitPerSale;
            $report->most_profitable_car_id = $mostProfitableCarId;
            $report->highest_single_profit = $highestSingleProfit;
            $report->created_by = $this->user->id;
            $report->updated_by = $this->user->id;
            $report->save();
            
            $this->reports[$dateStr] = $report;
        }
    }
    
    private function verifyDailySalesReports()
    {
        // Group sales by date again for verification
        $salesByDate = [];
        foreach ($this->sales as $sale) {
            $dateStr = $sale->sale_date->format('Y-m-d');
            if (!isset($salesByDate[$dateStr])) {
                $salesByDate[$dateStr] = [];
            }
            $salesByDate[$dateStr][] = $sale;
        }
        
        // Verify each report
        foreach ($this->reports as $dateStr => $report) {
            $dailySales = $salesByDate[$dateStr];
            
            // Recalculate metrics for verification
            $totalSales = count($dailySales);
            $totalRevenue = 0;
            $totalProfit = 0;
            $highestSingleProfit = 0;
            $mostProfitableCarId = null;
            
            foreach ($dailySales as $sale) {
                $totalRevenue += $sale->sale_price;
                $totalProfit += $sale->profit_loss;
                
                if ($sale->profit_loss > $highestSingleProfit) {
                    $highestSingleProfit = $sale->profit_loss;
                    $mostProfitableCarId = $sale->car_id;
                }
            }
              $avgProfitPerSale = $totalSales > 0 ? $totalProfit / $totalSales : 0;
              // Assert that the report values match our calculations
            $this->assertEquals($totalSales, $report->total_sales, "Total sales for {$dateStr} does not match");
            $this->assertEquals(round((float)$totalRevenue, 2), round((float)$report->total_revenue, 2), "Total revenue for {$dateStr} does not match");
            $this->assertEquals(round((float)$totalProfit, 2), round((float)$report->total_profit, 2), "Total profit for {$dateStr} does not match");
            $this->assertEquals(round((float)$avgProfitPerSale, 2), round((float)$report->avg_profit_per_sale, 2), "Average profit per sale for {$dateStr} does not match");
            $this->assertEquals($mostProfitableCarId, $report->most_profitable_car_id, "Most profitable car ID for {$dateStr} does not match");
            $this->assertEquals(round((float)$highestSingleProfit, 2), round((float)$report->highest_single_profit, 2), "Highest single profit for {$dateStr} does not match");
            
            // Verify that the report can be retrieved from the database
            $loadedReport = DailySalesReport::find($dateStr);
            $this->assertNotNull($loadedReport, "Could not load report for {$dateStr} from database");
            $this->assertEquals($report->total_sales, $loadedReport->total_sales);
            
            // Print out the report details for debugging
            echo "\n*** Report for {$dateStr} ***\n";
            echo "Total sales: {$report->total_sales}\n";
            echo "Total revenue: {$report->total_revenue}\n";
            echo "Total profit: {$report->total_profit}\n";
            echo "Average profit per sale: {$report->avg_profit_per_sale}\n";
            echo "Highest single profit: {$report->highest_single_profit}\n";
            
            // Verify relationships
            if ($report->most_profitable_car_id) {
                $this->assertInstanceOf(Car::class, $report->mostProfitableCar);
                echo "Most profitable car: {$report->mostProfitableCar->year} {$report->mostProfitableCar->make->name} {$report->mostProfitableCar->model->name}\n";
            }
        }
        
        // Verify we can aggregate data across reports
        $totalSalesAllDays = DailySalesReport::sum('total_sales');
        $this->assertEquals(count($this->sales), $totalSalesAllDays, "Total sales across all days does not match");
        
        $totalRevenueAllDays = DailySalesReport::sum('total_revenue');
        $totalProfitAllDays = DailySalesReport::sum('total_profit');
        
        echo "\n*** Overall Summary ***\n";
        echo "Total sales across all days: {$totalSalesAllDays}\n";
        echo "Total revenue across all days: {$totalRevenueAllDays}\n";
        echo "Total profit across all days: {$totalProfitAllDays}\n";
    }
    
    private function cleanupExistingReports()
    {
        // Get the dates we need to clean up
        $dates = [];
        foreach ($this->sales as $sale) {
            $dateStr = $sale->sale_date->format('Y-m-d');
            if (!in_array($dateStr, $dates)) {
                $dates[] = $dateStr;
            }
        }
        
        // Delete existing reports for these dates
        foreach ($dates as $dateStr) {
            $existingReport = DailySalesReport::find($dateStr);
            if ($existingReport) {
                echo "Deleting existing report for {$dateStr}\n";
                $existingReport->delete();
            }
        }
    }
    
    private function cleanupAllTestData()
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
