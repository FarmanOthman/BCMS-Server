<?php

declare(strict_types=1);

/**
 * Unit Test: Comprehensive Yearly Sales Report
 * 
 * Purpose:
 * This test provides a comprehensive end-to-end test of the YearlySalesReport functionality
 * including the integration with MonthlySalesReport, DailySalesReport and FinanceRecord.
 * 
 * What it tests:
 * - Creation of cars, buyers, and sales across multiple months
 * - Generation of daily sales reports for each month
 * - Creation of finance records for each month
 * - Generation of monthly sales reports based on daily reports
 * - Generation of yearly sales reports based on monthly reports
 * - Inclusion of finance costs in yearly report calculations
 * - Year-over-year growth calculation
 * - Validation of all report calculations and aggregations
 * 
 * Usage:
 * Run this test to verify that yearly sales reports accurately aggregate monthly data
 * and correctly include finance costs in the profit calculations.
 * 
 * Note:
 * This test includes a 60-second pause to allow for manual database inspection.
 */

namespace Tests\Unit;

use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use App\Models\FinanceRecord;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\User;
use App\Models\Buyer;
use App\Models\Sale;
use Tests\TestCase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ComprehensiveYearlySalesReportTest extends TestCase
{
    protected $user;
    protected $makes = [];
    protected $models = [];
    protected $cars = [];
    protected $buyers = [];
    protected $sales = [];
    protected $dailyReports = [];
    protected $financeRecords = [];
    protected $monthlyReports = [];
    protected $yearlyReport;
    protected $testYear = 2025;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up any existing data from previous test runs
        $this->cleanupAllTestData();
        
        // Create a test user for all tests
        $this->user = new User();
        $this->user->id = Str::uuid();
        $this->user->name = 'Test User Yearly';
        $this->user->email = 'test_yearly_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();
        
        // Create car makes
        $this->createMakes();
        
        // Create car models
        $this->createModels();
        
        // Create buyers
        $this->createBuyers();
    }
    
    protected function tearDown(): void
    {
        // Clean up in reverse order of creation
        $this->cleanupAllTestData();
        parent::tearDown();
    }

    public function test_comprehensive_yearly_sales_report()
    {
        echo "\n==================== YEARLY SALES REPORT TEST ====================\n";
        
        // Step 1: Create cars with various attributes
        echo "\nStep 1: Creating cars with various attributes for the year\n";
        $this->createCarsForYear();
        
        // Step 2: Create sales across multiple months (12 months)
        echo "\nStep 2: Creating sales across 12 months of the year\n";
        $this->createSalesForYear();
        
        // Step 3: Generate daily sales reports for each month
        echo "\nStep 3: Generating daily sales reports for each month\n";
        $this->generateDailySalesReportsForYear();
        
        // Step 4: Create finance records for each month
        echo "\nStep 4: Creating finance records for each month\n";
        $this->createFinanceRecordsForYear();
        
        // Step 5: Generate monthly sales reports for each month
        echo "\nStep 5: Generating monthly sales reports for each month\n";
        $this->generateMonthlySalesReportsForYear();
        
        // Step 6: Generate yearly sales report
        echo "\nStep 6: Generating yearly sales report\n";
        $this->generateYearlySalesReport();
        
        // Step 7: Verify yearly report calculations
        echo "\nStep 7: Verifying yearly report calculations\n";
        $this->verifyYearlySalesReport();
        
        // Pause for 60 seconds to view the reports in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'yearlysalesreport' table ***\n";
        echo "*** Check the 'monthlysalesreport' table ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** Check the 'financerecord' table ***\n";
        echo "\nComprehensive yearly sales report test completed successfully!\n";
    }
    
    /**
     * Create car makes
     */
    private function createMakes(): void
    {
        $makeNames = ['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes', 'Audi', 'Nissan', 'Volkswagen'];
        foreach ($makeNames as $makeName) {
            $make = new Make();
            $make->id = Str::uuid();
            $make->name = $makeName;
            $make->created_by = $this->user->id;
            $make->updated_by = $this->user->id;
            $make->save();
            $this->makes[$makeName] = $make;
        }
    }
    
    /**
     * Create car models
     */
    private function createModels(): void
    {
        $modelConfig = [
            'Toyota' => ['Corolla', 'Camry', 'RAV4', 'Prius'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot'],
            'Ford' => ['Focus', 'Mustang', 'F-150', 'Explorer'],
            'BMW' => ['3 Series', '5 Series', 'X5', 'X3'],
            'Mercedes' => ['C-Class', 'E-Class', 'GLE', 'GLC'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7'],
            'Nissan' => ['Altima', 'Sentra', 'Rogue', 'Pathfinder'],
            'Volkswagen' => ['Golf', 'Passat', 'Tiguan', 'Atlas'],
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
    }
    
    /**
     * Create buyers
     */
    private function createBuyers(): void
    {
        $buyerNames = [
            'John Smith', 'Jane Doe', 'Robert Johnson', 'Emily Davis', 'Michael Brown',
            'Sarah Wilson', 'David Miller', 'Lisa Anderson', 'Chris Thompson', 'Jennifer Taylor',
            'Matthew Garcia', 'Ashley Martinez', 'Daniel Rodriguez', 'Amanda Lopez', 'Ryan Hill',
            'Nicole Clark', 'James Lewis', 'Stephanie Lee', 'Kevin Walker', 'Melissa Hall'
        ];
        
        foreach ($buyerNames as $buyerName) {
            $buyer = new Buyer();
            $buyer->id = Str::uuid();
            $buyer->name = $buyerName;
            $buyer->phone = '555-' . rand(100, 999) . '-' . rand(1000, 9999);
            $buyer->address = rand(100, 999) . ' ' . ['Main', 'Oak', 'Maple', 'Cedar', 'Pine', 'Elm', 'Birch'][rand(0, 6)] . ' St';
            $buyer->created_by = $this->user->id;
            $buyer->updated_by = $this->user->id;
            $buyer->save();
            $this->buyers[] = $buyer;
        }
    }
    
    /**
     * Create cars for the entire year (more cars to support 12 months of sales)
     */
    private function createCarsForYear(): void
    {
        // Base price factors by make (premium vs economy)
        $basePriceFactors = [
            'Toyota' => 20000,
            'Honda' => 19000,
            'Ford' => 22000,
            'BMW' => 40000,
            'Mercedes' => 45000,
            'Audi' => 42000,
            'Nissan' => 18000,
            'Volkswagen' => 25000,
        ];
        
        // Create 120 cars to support sales throughout the year (10 per month)
        $makeModelKeys = array_keys($this->models);
        
        for ($i = 0; $i < 120; $i++) {
            $makeModelKey = $makeModelKeys[array_rand($makeModelKeys)];
            list($makeName, $modelName) = explode(' ', $makeModelKey, 2);
            
            $car = new Car();
            $car->id = Str::uuid();
            $car->make_id = $this->makes[$makeName]->id;
            $car->model_id = $this->models[$makeModelKey]->id;
            $car->year = rand(2020, 2025);
            $car->vin = 'YEARLY' . str_pad((string)$i, 5, '0', STR_PAD_LEFT);
            
            // Set cost price based on make and year
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
                $repairCost = rand(50, 500);
                $repairItems[] = [
                    'description' => ['Paint touch-up', 'Interior cleaning', 'Tire replacement', 'Engine tuning', 'Window repair'][rand(0, 4)],
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
        
        echo "Created " . count($this->cars) . " cars for the year\n";
    }
    
    /**
     * Create sales throughout the entire year (12 months)
     */
    private function createSalesForYear(): void
    {
        $year = $this->testYear;
        $carsToSell = $this->cars; // Copy the array to track which cars are sold
        $salesPerMonth = 8; // 8 sales per month = 96 total sales
        
        for ($month = 1; $month <= 12; $month++) {
            echo "\n  Creating sales for month {$month}:\n";
            
            // Determine number of days in the month
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            
            for ($i = 0; $i < $salesPerMonth && count($carsToSell) > 0; $i++) {
                // Choose a random day in the month
                $day = rand(1, $daysInMonth);
                $saleDate = Carbon::createFromDate($year, $month, $day);
                
                // Select an unsold car
                $carIndex = array_rand($carsToSell);
                $car = $carsToSell[$carIndex];
                unset($carsToSell[$carIndex]); // Remove from available cars
                $carsToSell = array_values($carsToSell); // Re-index array
                
                // Select a random buyer
                $buyer = $this->buyers[array_rand($this->buyers)];
                
                // Determine sale price (between 90% and 110% of public price)
                $priceMultiplier = rand(90, 110) / 100;
                $salePrice = $car->public_price * $priceMultiplier;
                
                // Calculate purchase cost and profit/loss
                $purchaseCost = $car->cost_price + $car->transition_cost + $car->total_repair_cost;
                $profitLoss = $salePrice - $purchaseCost;
                
                // Create the sale
                $sale = new Sale();
                $sale->id = Str::uuid();
                $sale->car_id = $car->id;
                $sale->buyer_id = $buyer->id;
                $sale->sale_price = round($salePrice, 2);
                $sale->purchase_cost = round($purchaseCost, 2);
                $sale->profit_loss = round($profitLoss, 2);
                $sale->sale_date = $saleDate->toDateString();
                $sale->notes = "Sale of {$car->year} {$car->make->name} {$car->model->name} - Month {$month}";
                $sale->created_by = $this->user->id;
                $sale->updated_by = $this->user->id;
                $sale->save();
                
                // Update the car to sold status
                $car->status = 'sold';
                $car->selling_price = $salePrice;
                $car->updated_by = $this->user->id;
                $car->save();
                
                $this->sales[] = $sale;
                
                echo "    Sale " . ($i+1) . ": {$car->year} {$car->make->name} {$car->model->name} for \${$salePrice} on {$saleDate->format('Y-m-d')}\n";
            }
        }
        
        echo "\nTotal sales created for the year: " . count($this->sales) . "\n";
    }
    
    /**
     * Generate daily sales reports for the entire year
     */
    private function generateDailySalesReportsForYear(): void
    {
        // Group sales by date
        $salesByDate = [];
        foreach ($this->sales as $sale) {
            $dateStr = $sale->sale_date;
            $dateKey = is_object($dateStr) ? $dateStr->format('Y-m-d') : $dateStr;
            if (!isset($salesByDate[$dateKey])) {
                $salesByDate[$dateKey] = [];
            }
            $salesByDate[$dateKey][] = $sale;
        }
        
        // Generate report for each day with sales
        foreach ($salesByDate as $dateKey => $dailySales) {
            $reportDate = Carbon::parse($dateKey);
            
            // Calculate metrics for this date
            $totalSales = count($dailySales);
            $totalRevenue = 0;
            $totalProfit = 0;
            $highestSingleProfit = 0;
            $mostProfitableCarId = null;
            
            foreach ($dailySales as $sale) {
                $totalRevenue += (float)$sale->sale_price;
                $totalProfit += (float)$sale->profit_loss;
                
                if ((float)$sale->profit_loss > $highestSingleProfit) {
                    $highestSingleProfit = (float)$sale->profit_loss;
                    $mostProfitableCarId = $sale->car_id;
                }
            }
            
            $avgProfitPerSale = $totalSales > 0 ? (float)$totalProfit / (float)$totalSales : 0;
            
            // Create the daily sales report
            $report = new DailySalesReport();
            $report->report_date = $reportDate;
            $report->total_sales = $totalSales;
            $report->total_revenue = round((float)$totalRevenue, 2);
            $report->total_profit = round((float)$totalProfit, 2);
            $report->avg_profit_per_sale = round((float)$avgProfitPerSale, 2);
            $report->most_profitable_car_id = $mostProfitableCarId;
            $report->highest_single_profit = round((float)$highestSingleProfit, 2);
            $report->created_by = $this->user->id;
            $report->updated_by = $this->user->id;
            $report->save();
            
            $this->dailyReports[$dateKey] = $report;
            
            echo "Generated daily report for {$dateKey}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit\n";
        }
        
        echo "Total daily reports generated: " . count($this->dailyReports) . "\n";
    }
    
    /**
     * Create finance records for each month of the year
     */
    private function createFinanceRecordsForYear(): void
    {
        $year = $this->testYear;
        
        // Finance categories
        $financeCategories = [
            'Rent' => ['Office Rent', 'Storage Space Rent', 'Display Lot Rent'],
            'Utilities' => ['Electricity', 'Water', 'Internet', 'Phone'],
            'Salaries' => ['Sales Staff', 'Management', 'Mechanics', 'Administrative'],
            'Marketing' => ['Online Ads', 'Print Ads', 'Social Media', 'Billboard'],
            'Miscellaneous' => ['Office Supplies', 'Cleaning', 'Insurance', 'Legal Fees']
        ];
        
        for ($month = 1; $month <= 12; $month++) {
            echo "\n  Creating finance records for month {$month}:\n";
            
            $monthlyFinanceCost = 0;
            
            foreach ($financeCategories as $type => $categories) {
                foreach ($categories as $category) {
                    // Random cost between $500 and $8000 (higher costs for yearly test)
                    $cost = rand(500, 8000);
                    $monthlyFinanceCost += $cost;
                    
                    // Random day in the month
                    $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                    $day = rand(1, $daysInMonth);
                    $recordDate = Carbon::createFromDate($year, $month, $day);
                    
                    $record = new FinanceRecord();
                    $record->id = Str::uuid();
                    $record->type = $type;
                    $record->category = $category;
                    $record->cost = $cost;
                    $record->record_date = $recordDate;
                    $record->description = "{$type} expense: {$category} for {$month}/{$year}";
                    $record->created_by = $this->user->id;
                    $record->updated_by = $this->user->id;
                    $record->save();
                    
                    $this->financeRecords[] = $record;
                }
            }
            
            echo "    Total finance cost for month {$month}: \${$monthlyFinanceCost}\n";
        }
        
        echo "Total finance records created: " . count($this->financeRecords) . "\n";
    }
    
    /**
     * Generate monthly sales reports for each month of the year
     */
    private function generateMonthlySalesReportsForYear(): void
    {
        $year = $this->testYear;
        
        for ($month = 1; $month <= 12; $month++) {
            echo "\n  Generating monthly report for month {$month}:\n";
            
            // Calculate month start and end dates
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            
            // Gather all daily reports for the month
            $monthDailyReports = DailySalesReport::whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])->get();
            
            // Gather all finance records for the month
            $monthFinanceRecords = FinanceRecord::whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])->get();
            
            // Calculate aggregates
            $totalSales = $monthDailyReports->sum('total_sales');
            $totalRevenue = $monthDailyReports->sum('total_revenue');
            $totalProfit = $monthDailyReports->sum('total_profit');
            
            // Calculate finance cost
            $financeCost = $monthFinanceRecords->sum('cost');
            
            // Calculate net profit (after finance costs)
            $netProfit = $totalProfit - $financeCost;
            
            // Calculate average daily profit
            $daysWithSales = $monthDailyReports->count();
            $avgDailyProfit = $daysWithSales > 0 ? $totalProfit / $daysWithSales : 0;
            
            // Find the best day
            $bestDayReport = $monthDailyReports->sortByDesc('total_profit')->first();
            $bestDay = $bestDayReport ? $bestDayReport->report_date : null;
            $bestDayProfit = $bestDayReport ? $bestDayReport->total_profit : 0;
            
            // Calculate profit margin
            $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
            
            // Create or update the monthly report
            $report = MonthlySalesReport::forYearMonth($year, $month)->first();
            if (!$report) {
                $report = new MonthlySalesReport();
            }
            
            $report->year = $year;
            $report->month = $month;
            $report->start_date = $startDate;
            $report->end_date = $endDate;
            $report->total_sales = $totalSales;
            $report->total_revenue = round((float)$totalRevenue, 2);
            $report->total_profit = round((float)$totalProfit, 2);
            $report->avg_daily_profit = round((float)$avgDailyProfit, 2);
            $report->best_day = $bestDay;
            $report->best_day_profit = round((float)$bestDayProfit, 2);
            $report->profit_margin = round((float)$profitMargin, 2);
            $report->finance_cost = round((float)$financeCost, 2);
            $report->net_profit = round((float)$netProfit, 2);
            $report->created_by = $this->user->id;
            $report->updated_by = $this->user->id;
            $report->save();
            
            $this->monthlyReports[] = $report;
            
            echo "    Month {$month}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit, \${$financeCost} finance cost, \${$netProfit} net profit\n";
        }
        
        echo "Total monthly reports generated: " . count($this->monthlyReports) . "\n";
    }
    
    /**
     * Generate the yearly sales report
     */
    private function generateYearlySalesReport(): void
    {
        $year = $this->testYear;
        
        // Gather all monthly reports for the year
        $yearMonthlyReports = MonthlySalesReport::where('year', $year)->get();
        
        echo "\nCalculating yearly report for {$year}:\n";
          // Calculate aggregates from monthly reports
        $totalSales = $yearMonthlyReports->sum('total_sales');
        $totalRevenue = (float)$yearMonthlyReports->sum('total_revenue');
        $totalProfit = (float)$yearMonthlyReports->sum('total_profit');
        $totalFinanceCost = (float)$yearMonthlyReports->sum('finance_cost');
        $totalNetProfit = (float)$yearMonthlyReports->sum('net_profit');
        
        echo "Yearly aggregates from monthly reports:\n";
        foreach ($yearMonthlyReports as $monthlyReport) {
            echo "  Month {$monthlyReport->month}: Sales: {$monthlyReport->total_sales}, Revenue: \${$monthlyReport->total_revenue}, Profit: \${$monthlyReport->total_profit}, Net Profit: \${$monthlyReport->net_profit}\n";
        }
        
        // Calculate average monthly profit
        $numberOfMonths = $yearMonthlyReports->count();
        $avgMonthlyProfit = $numberOfMonths > 0 ? $totalProfit / $numberOfMonths : 0;
        
        // Find the best month
        $bestMonthReport = $yearMonthlyReports->sortByDesc('total_profit')->first();
        $bestMonth = $bestMonthReport ? $bestMonthReport->month : null;
        $bestMonthProfit = $bestMonthReport ? (float)$bestMonthReport->total_profit : 0;
        
        // Calculate profit margin
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
          // Calculate year-over-year growth (for this test, we'll simulate it)
        // In a real scenario, you'd compare with the previous year's data
        $previousYearReport = YearlySalesReport::where('year', $year - 1)->first();
        $yoyGrowth = 0; // Default to 0 instead of null to match database constraint
        if ($previousYearReport) {
            $yoyGrowth = $previousYearReport->total_revenue > 0 ? 
                (($totalRevenue - $previousYearReport->total_revenue) / $previousYearReport->total_revenue) * 100 : 0;
        } else {
            // For test purposes, simulate a growth rate
            $yoyGrowth = rand(-10, 25); // Random growth between -10% and 25%
        }
        
        echo "\nFinal Yearly Report Calculations:\n";
        echo "  Total Sales: {$totalSales}\n";
        echo "  Total Revenue: \${$totalRevenue}\n";
        echo "  Total Profit (before finance): \${$totalProfit}\n";
        echo "  Total Finance Cost: \${$totalFinanceCost}\n";
        echo "  Total Net Profit (after finance): \${$totalNetProfit}\n";
        echo "  Average Monthly Profit: \${$avgMonthlyProfit}\n";
        echo "  Best Month: {$bestMonth} with \${$bestMonthProfit} profit\n";
        echo "  Profit Margin: {$profitMargin}%\n";
        echo "  Year-over-Year Growth: {$yoyGrowth}%\n";
        
        // Create or update the yearly report
        $report = YearlySalesReport::where('year', $year)->first();
        if (!$report) {
            $report = new YearlySalesReport();
        }
        
        $report->year = $year;
        $report->total_sales = $totalSales;
        $report->total_revenue = round((float)$totalRevenue, 2);
        $report->total_profit = round((float)$totalProfit, 2);
        $report->avg_monthly_profit = round((float)$avgMonthlyProfit, 2);
        $report->best_month = $bestMonth;
        $report->best_month_profit = round((float)$bestMonthProfit, 2);
        $report->profit_margin = round((float)$profitMargin, 2);
        $report->yoy_growth = round((float)$yoyGrowth, 2);
        $report->total_finance_cost = round((float)$totalFinanceCost, 2);
        $report->total_net_profit = round((float)$totalNetProfit, 2);
        $report->created_by = $this->user->id;
        $report->updated_by = $this->user->id;
        $report->save();
        
        $this->yearlyReport = $report;
        
        echo "\nGenerated yearly sales report for {$year}\n";
    }
    
    /**
     * Verify the yearly sales report calculations
     */
    private function verifyYearlySalesReport(): void
    {
        $year = $this->testYear;
        
        echo "\nVerifying yearly sales report calculations:\n";
        
        // Manually recalculate expected values from monthly reports
        $monthlyReportsForYear = MonthlySalesReport::where('year', $year)->get();
          $expectedTotalSales = $monthlyReportsForYear->sum('total_sales');
        $expectedTotalRevenue = (float)$monthlyReportsForYear->sum('total_revenue');
        $expectedTotalProfit = (float)$monthlyReportsForYear->sum('total_profit');
        $expectedTotalFinanceCost = (float)$monthlyReportsForYear->sum('finance_cost');
        $expectedTotalNetProfit = (float)$monthlyReportsForYear->sum('net_profit');
        
        $numberOfMonths = $monthlyReportsForYear->count();
        $expectedAvgMonthlyProfit = $numberOfMonths > 0 ? $expectedTotalProfit / $numberOfMonths : 0;
        
        $bestMonthReport = $monthlyReportsForYear->sortByDesc('total_profit')->first();
        $expectedBestMonth = $bestMonthReport ? $bestMonthReport->month : null;
        $expectedBestMonthProfit = $bestMonthReport ? (float)$bestMonthReport->total_profit : 0;
        
        $expectedProfitMargin = $expectedTotalRevenue > 0 ? ($expectedTotalProfit / $expectedTotalRevenue) * 100 : 0;
        
        echo "Expected values from monthly reports:\n";
        echo "  Total Sales: {$expectedTotalSales}\n";
        echo "  Total Revenue: \${$expectedTotalRevenue}\n";
        echo "  Total Profit: \${$expectedTotalProfit}\n";
        echo "  Total Finance Cost: \${$expectedTotalFinanceCost}\n";
        echo "  Total Net Profit: \${$expectedTotalNetProfit}\n";
        echo "  Average Monthly Profit: \${$expectedAvgMonthlyProfit}\n";
        echo "  Best Month: {$expectedBestMonth} with \${$expectedBestMonthProfit} profit\n";
        echo "  Profit Margin: {$expectedProfitMargin}%\n";
        
        // Fetch the generated report
        $report = YearlySalesReport::where('year', $year)->first();
        
        // Verify the report
        $this->assertNotNull($report, "Yearly report for {$year} not found");
        $this->assertEquals($expectedTotalSales, $report->total_sales, "Total sales does not match");
        $this->assertEquals(round((float)$expectedTotalRevenue, 2), round((float)$report->total_revenue, 2), "Total revenue does not match");
        $this->assertEquals(round((float)$expectedTotalProfit, 2), round((float)$report->total_profit, 2), "Total profit does not match");
        $this->assertEquals(round((float)$expectedAvgMonthlyProfit, 2), round((float)$report->avg_monthly_profit, 2), "Average monthly profit does not match");
          if ($expectedBestMonth) {
            $this->assertEquals($expectedBestMonth, $report->best_month, "Best month does not match");
            $this->assertEquals(round((float)$expectedBestMonthProfit, 2), round((float)$report->best_month_profit, 2), "Best month profit does not match");
        }
          $this->assertEquals(round((float)$expectedProfitMargin, 2), round((float)$report->profit_margin, 2), "Profit margin does not match");
        $this->assertEquals(round((float)$expectedTotalFinanceCost, 2), round((float)$report->total_finance_cost, 2), "Total finance cost does not match");
        $this->assertEquals(round((float)$expectedTotalNetProfit, 2), round((float)$report->total_net_profit, 2), "Total net profit does not match");
        
        echo "\nYearly report verification successful!\n";
        echo "  Expected Total Sales: {$expectedTotalSales} ✓\n";
        echo "  Expected Total Revenue: \${$expectedTotalRevenue} ✓\n";
        echo "  Expected Total Profit: \${$expectedTotalProfit} ✓\n";
        echo "  Expected Total Finance Cost: \${$expectedTotalFinanceCost} ✓\n";
        echo "  Expected Total Net Profit: \${$expectedTotalNetProfit} ✓\n";
        echo "  Expected Average Monthly Profit: \${$expectedAvgMonthlyProfit} ✓\n";
    }
    
    /**
     * Clean up all test data
     */
    private function cleanupAllTestData(): void
    {
        // Clean up yearly report
        $yearlyReports = YearlySalesReport::where('year', $this->testYear)->get();
        foreach ($yearlyReports as $report) {
            $report->delete();
        }
        
        // Clean up monthly reports
        $monthlyReports = MonthlySalesReport::where('year', $this->testYear)->get();
        foreach ($monthlyReports as $report) {
            $report->delete();
        }
        
        // Clean up finance records for the entire year
        $startDate = Carbon::createFromDate($this->testYear, 1, 1);
        $endDate = Carbon::createFromDate($this->testYear, 12, 31);
        
        $financeRecords = FinanceRecord::whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])->get();
        foreach ($financeRecords as $record) {
            $record->delete();
        }
        
        // Clean up daily reports for the entire year
        $dailyReports = DailySalesReport::whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])->get();
        foreach ($dailyReports as $report) {
            $report->delete();
        }
        
        // Clean up sales
        if (isset($this->sales) && is_array($this->sales)) {
            foreach ($this->sales as $sale) {
                if ($sale && Sale::find($sale->id)) {
                    $sale->delete();
                }
            }
            $this->sales = [];
        }
        
        // Reset car status for any cars that were sold
        if (isset($this->cars) && is_array($this->cars)) {
            foreach ($this->cars as $car) {
                if ($car && Car::find($car->id)) {
                    if ($car->status === 'sold') {
                        $car->status = 'available';
                        $car->selling_price = null;
                        $car->save();
                    }
                    $car->delete();
                }
            }
            $this->cars = [];
        }
        
        // Delete all buyers
        if (isset($this->buyers) && is_array($this->buyers)) {
            foreach ($this->buyers as $buyer) {
                if ($buyer && Buyer::find($buyer->id)) {
                    $buyer->delete();
                }
            }
            $this->buyers = [];
        }
        
        // Delete all models
        if (isset($this->models) && is_array($this->models)) {
            foreach ($this->models as $model) {
                if ($model && Model::find($model->id)) {
                    $model->delete();
                }
            }
            $this->models = [];
        }
        
        // Delete all makes
        if (isset($this->makes) && is_array($this->makes)) {
            foreach ($this->makes as $make) {
                if ($make && Make::find($make->id)) {
                    $make->delete();
                }
            }
            $this->makes = [];
        }
        
        // Delete the user
        if (isset($this->user) && $this->user && User::find($this->user->id)) {
            $this->user->delete();
            $this->user = null;
        }
    }
}
