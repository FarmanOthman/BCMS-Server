<?php

declare(strict_types=1);

/**
 * Unit Test: Comprehensive Monthly Sales Report
 * 
 * Purpose:
 * This test provides a comprehensive end-to-end test of the MonthlySalesReport functionality
 * including the integration with DailySalesReport and FinanceRecord.
 * 
 * What it tests:
 * - Creation of cars, buyers, and sales
 * - Generation of daily sales reports
 * - Creation of finance records
 * - Generation of monthly sales reports based on daily reports
 * - Inclusion of finance costs in monthly report calculations
 * - Validation of all report calculations and aggregations
 * 
 * Usage:
 * Run this test to verify that monthly sales reports accurately aggregate daily data
 * and correctly include finance costs in the profit calculations.
 * 
 * Note:
 * This test includes a 60-second pause to allow for manual database inspection.
 */

namespace Tests\Unit;

use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
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

class ComprehensiveMonthlySalesReportTest extends TestCase
{
    protected $user;
    protected $makes = [];
    protected $models = [];
    protected $cars = [];
    protected $buyers = [];
    protected $sales = [];
    protected $dailyReports = [];
    protected $financeRecords = [];
    protected $monthlyReport;

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

    public function test_comprehensive_monthly_sales_report()
    {
        // Step 1: Create cars with various attributes
        echo "\nStep 1: Creating cars with various attributes\n";
        $this->createCars();
        
        // Step 2: Create sales across different days of the month
        echo "\nStep 2: Creating sales across different days of the month\n";
        $this->createSales();
        
        // Step 3: Generate daily sales reports
        echo "\nStep 3: Generating daily sales reports\n";
        $this->generateDailySalesReports();
        
        // Step 4: Create finance records for the month
        echo "\nStep 4: Creating finance records for the month\n";
        $this->createFinanceRecords();
        
        // Step 5: Generate monthly sales report
        echo "\nStep 5: Generating monthly sales report\n";
        $this->generateMonthlySalesReport();
        
        // Step 6: Verify monthly report calculations
        echo "\nStep 6: Verifying monthly report calculations\n";
        $this->verifyMonthlySalesReport();
        
        // Pause for 60 seconds to view the reports in the database
        echo "\n*** Pausing for 60 seconds so you can check the database ***\n";
        echo "*** Check the 'monthlysalesreport' table ***\n";
        echo "*** Check the 'dailysalesreport' table ***\n";
        echo "*** Check the 'financerecord' table ***\n";
        sleep(60);
    }
    
    /**
     * Create car makes
     */
    private function createMakes(): void
    {
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
    }
    
    /**
     * Create car models
     */
    private function createModels(): void
    {
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
    }
    
    /**
     * Create buyers
     */
    private function createBuyers(): void
    {
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
    
    /**
     * Create cars with varying attributes
     */
    private function createCars(): void
    {
        // Base price factors by make (premium vs economy)
        $basePriceFactors = [
            'Toyota' => 20000,
            'Honda' => 19000,
            'Ford' => 22000,
            'BMW' => 40000,
            'Mercedes' => 45000,
        ];
        
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
            
            echo "Created car: {$car->year} {$makeName} {$modelName}, Cost: \${$car->cost_price}, Public Price: \${$car->public_price}\n";
        }
    }
    
    /**
     * Create sales throughout the month
     */
    private function createSales(): void
    {
        // Create a test month - use June 2025 (current month)
        $year = 2025;
        $month = 6;
        
        // Determine number of days in the month
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        
        // Create sales distributed throughout the month
        $carsToSell = $this->cars; // Copy the array to track which cars are sold
        $numCarsToSell = min(count($carsToSell), 10); // Sell up to 10 cars
        
        for ($i = 0; $i < $numCarsToSell; $i++) {
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
            
            // Debug output to see the exact profit calculation
            echo "Profit calculation for {$car->year} {$car->make->name} {$car->model->name}:\n";
            echo "  Sale Price: \${$salePrice}\n";
            echo "  Cost Price: \${$car->cost_price}\n";
            echo "  Transition Cost: \${$car->transition_cost}\n";
            echo "  Repair Cost: \${$car->total_repair_cost}\n";
            echo "  Total Cost: \${$purchaseCost}\n";
            echo "  Profit: \${$profitLoss}\n\n";
              // Create the sale
            $sale = new Sale();
            $sale->id = Str::uuid();
            $sale->car_id = $car->id;
            $sale->buyer_id = $buyer->id;
            $sale->sale_price = round($salePrice, 2); // Ensure proper rounding to 2 decimal places
            $sale->purchase_cost = round($purchaseCost, 2); // Ensure proper rounding
            $sale->profit_loss = round($profitLoss, 2); // Ensure proper rounding
            $sale->sale_date = $saleDate->toDateString();
            $sale->notes = "Sale of {$car->year} {$car->make->name} {$car->model->name}";
            $sale->created_by = $this->user->id;
            $sale->updated_by = $this->user->id;
            $sale->save();
            
            // Update the car to sold status
            $car->status = 'sold';
            $car->selling_price = $salePrice;
            $car->updated_by = $this->user->id;
            $car->save();
            
            $this->sales[] = $sale;
            
            echo "Created sale for {$car->year} {$car->make->name} {$car->model->name} to {$buyer->name} for \${$salePrice} on {$saleDate->format('Y-m-d')}\n";
        }
    }
      /**
     * Generate daily sales reports for days with sales
     */
    private function generateDailySalesReports(): void
    {
        // Group sales by date
        $salesByDate = [];
        foreach ($this->sales as $sale) {
            $dateStr = $sale->sale_date;
            // Ensure we're using a string key by formatting the date
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
            
            echo "\nCalculating daily sales report for {$dateKey}:\n";
            
            foreach ($dailySales as $sale) {
                $totalRevenue += (float)$sale->sale_price;
                $totalProfit += (float)$sale->profit_loss;
                
                // Debug output for each sale's contribution to daily report
                $car = Car::find($sale->car_id);
                echo "  Sale included: {$car->year} {$car->make->name} {$car->model->name}\n";
                echo "    Sale Price: \${$sale->sale_price}\n";
                echo "    Purchase Cost: \${$sale->purchase_cost}\n";
                echo "    Profit/Loss: \${$sale->profit_loss}\n";
                
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
            $report->total_revenue = round((float)$totalRevenue, 2); // Ensure proper rounding
            $report->total_profit = round((float)$totalProfit, 2); // Ensure proper rounding
            $report->avg_profit_per_sale = round((float)$avgProfitPerSale, 2); // Ensure proper rounding
            $report->most_profitable_car_id = $mostProfitableCarId;
            $report->highest_single_profit = round((float)$highestSingleProfit, 2); // Ensure proper rounding$report->created_by = $this->user->id;
            $report->updated_by = $this->user->id;
            $report->save();
            
            // Store the report using the string date key for consistency
            $this->dailyReports[$dateKey] = $report;
            
            echo "Generated daily sales report for {$dateKey}: {$totalSales} sales, \${$totalRevenue} revenue, \${$totalProfit} profit\n";
        }
    }
    
    /**
     * Create finance records for the month
     */
    private function createFinanceRecords(): void
    {
        // Create a test month - use June 2025 (current month)
        $year = 2025;
        $month = 6;
        
        // Finance categories
        $financeCategories = [
            'Rent' => ['Office Rent', 'Storage Space Rent'],
            'Utilities' => ['Electricity', 'Water', 'Internet'],
            'Salaries' => ['Sales Staff', 'Management', 'Mechanics'],
            'Marketing' => ['Online Ads', 'Print Ads', 'Social Media'],
            'Miscellaneous' => ['Office Supplies', 'Cleaning', 'Insurance']
        ];
        
        // Create a finance record for each category
        $totalFinanceCost = 0;
        
        foreach ($financeCategories as $type => $categories) {
            foreach ($categories as $category) {
                // Random cost between $500 and $5000
                $cost = rand(500, 5000);
                $totalFinanceCost += $cost;
                
                // Random day in the month
                $day = rand(1, 28);
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
                
                echo "Created finance record: {$type} - {$category} for \${$cost} on {$recordDate->format('Y-m-d')}\n";
            }
        }
        
        echo "Total finance cost for {$month}/{$year}: \${$totalFinanceCost}\n";
    }
    
    /**
     * Generate the monthly sales report
     */
    private function generateMonthlySalesReport(): void
    {
        // Create a test month - use June 2025 (current month)
        $year = 2025;
        $month = 6;
        
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
        
        // Debug output for all daily reports
        echo "\nMonthly Report Calculation Details:\n";
        echo "  Daily Reports included in calculation:\n";
        foreach ($monthDailyReports as $dailyReport) {
            echo "    {$dailyReport->report_date->format('Y-m-d')}: Sales: {$dailyReport->total_sales}, Revenue: \${$dailyReport->total_revenue}, Profit: \${$dailyReport->total_profit}\n";
        }
        
        // Calculate finance cost
        $financeCost = $monthFinanceRecords->sum('cost');
        
        echo "  Finance Records included in calculation:\n";
        foreach ($monthFinanceRecords as $record) {
            echo "    {$record->record_date->format('Y-m-d')}: {$record->type} - {$record->category}: \${$record->cost}\n";
        }
        echo "  Total Finance Cost: \${$financeCost}\n";
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
        
        echo "\nFinal Monthly Report Calculations:\n";
        echo "  Total Sales: {$totalSales}\n";
        echo "  Total Revenue: \${$totalRevenue}\n";
        echo "  Total Profit: \${$totalProfit}\n";
        echo "  Finance Cost: \${$financeCost}\n";
        echo "  Net Profit: \${$netProfit}\n";
        echo "  Profit Margin: {$profitMargin}%\n";
        
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
        $report->total_revenue = round((float)$totalRevenue, 2); // Ensure proper rounding
        $report->total_profit = round((float)$totalProfit, 2); // Ensure proper rounding
        $report->avg_daily_profit = round((float)$avgDailyProfit, 2); // Ensure proper rounding
        $report->best_day = $bestDay;
        $report->best_day_profit = round((float)$bestDayProfit, 2); // Ensure proper rounding
        $report->profit_margin = round((float)$profitMargin, 2); // Ensure proper rounding
        $report->finance_cost = round((float)$financeCost, 2); // Ensure proper rounding
        $report->net_profit = round((float)$netProfit, 2); // Ensure proper rounding
        $report->created_by = $this->user->id;
        $report->updated_by = $this->user->id;
        $report->save();
        
        $this->monthlyReport = $report;
        
        echo "Generated monthly sales report for {$month}/{$year}:\n";
        echo "  Total Sales: {$totalSales}\n";
        echo "  Total Revenue: \${$totalRevenue}\n";
        echo "  Total Profit (before finance): \${$totalProfit}\n";
        echo "  Finance Cost: \${$financeCost}\n";
        echo "  Net Profit (after finance): \${$netProfit}\n";
        echo "  Profit Margin: {$profitMargin}%\n";
    }
    
    /**
     * Verify the monthly sales report calculations
     */
    private function verifyMonthlySalesReport(): void
    {
        // Create a test month - use June 2025 (current month)
        $year = 2025;
        $month = 6;
        
        // Calculate month start and end dates
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Manually recalculate expected values
        $expectedTotalSales = 0;
        $expectedTotalRevenue = 0;
        $expectedTotalProfit = 0;
        $bestDayProfit = 0;
        $bestDayDate = null;
        
        // Get daily reports for manual calculation
        $dailyReportsForMonth = DailySalesReport::whereBetween('report_date', [
            $startDate->toDateString(), 
            $endDate->toDateString()
        ])->get();
        
        echo "\nVerification - Recalculating from daily reports:\n";
        
        foreach ($dailyReportsForMonth as $dailyReport) {
            $expectedTotalSales += $dailyReport->total_sales;
            $expectedTotalRevenue += (float)$dailyReport->total_revenue;
            $expectedTotalProfit += (float)$dailyReport->total_profit;
            
            echo "  Daily Report {$dailyReport->report_date->format('Y-m-d')}: Sales: {$dailyReport->total_sales}, Revenue: \${$dailyReport->total_revenue}, Profit: \${$dailyReport->total_profit}\n";
            
            if ((float)$dailyReport->total_profit > $bestDayProfit) {
                $bestDayProfit = (float)$dailyReport->total_profit;
                $bestDayDate = $dailyReport->report_date;
            }
        }
        
        // Double-check by directly querying sales
        echo "\nVerification - Recalculating directly from sales records:\n";
        $salesForMonth = Sale::whereBetween('sale_date', [
            $startDate->toDateString(), 
            $endDate->toDateString()
        ])->get();
        
        $directTotalSales = $salesForMonth->count();
        $directTotalRevenue = 0;
        $directTotalProfit = 0;
        
        foreach ($salesForMonth as $sale) {
            $directTotalRevenue += (float)$sale->sale_price;
            $directTotalProfit += (float)$sale->profit_loss;
            
            $car = Car::find($sale->car_id);
            echo "  Sale: {$car->year} {$car->make->name} {$car->model->name} on {$sale->sale_date}\n";
            echo "    Sale Price: \${$sale->sale_price}, Cost: \${$sale->purchase_cost}, Profit: \${$sale->profit_loss}\n";
        }
        
        echo "\nVerification - Comparison:\n";
        echo "  From Daily Reports: Sales: {$expectedTotalSales}, Revenue: \${$expectedTotalRevenue}, Profit: \${$expectedTotalProfit}\n";
        echo "  Directly from Sales: Sales: {$directTotalSales}, Revenue: \${$directTotalRevenue}, Profit: \${$directTotalProfit}\n";
        
        // Calculate expected finance costs
        $expectedFinanceCost = 0;
        $financeRecordsForMonth = FinanceRecord::whereBetween('record_date', [
            $startDate->toDateString(), 
            $endDate->toDateString()
        ])->get();
        
        foreach ($financeRecordsForMonth as $financeRecord) {
            $expectedFinanceCost += (float)$financeRecord->cost;
        }
        
        $expectedNetProfit = $expectedTotalProfit - $expectedFinanceCost;
        
        // Calculate expected average daily profit
        $daysWithSales = $dailyReportsForMonth->count();
        $expectedAvgDailyProfit = $daysWithSales > 0 ? $expectedTotalProfit / $daysWithSales : 0;
        
        // Calculate expected profit margin
        $expectedProfitMargin = $expectedTotalRevenue > 0 ? ($expectedTotalProfit / $expectedTotalRevenue) * 100 : 0;
        
        // Fetch the generated report
        $report = MonthlySalesReport::forYearMonth($year, $month)->first();
        
        // Verify the report
        $this->assertNotNull($report, "Monthly report for {$month}/{$year} not found");
        $this->assertEquals($expectedTotalSales, $report->total_sales, "Total sales does not match");
        $this->assertEquals(round((float)$expectedTotalRevenue, 2), round((float)$report->total_revenue, 2), "Total revenue does not match");
        $this->assertEquals(round((float)$expectedTotalProfit, 2), round((float)$report->total_profit, 2), "Total profit does not match");
        $this->assertEquals(round((float)$expectedAvgDailyProfit, 2), round((float)$report->avg_daily_profit, 2), "Average daily profit does not match");
        
        if ($bestDayDate) {
            $this->assertEquals($bestDayDate->format('Y-m-d'), $report->best_day->format('Y-m-d'), "Best day does not match");
            $this->assertEquals(round($bestDayProfit, 2), round((float)$report->best_day_profit, 2), "Best day profit does not match");
        }
          $this->assertEquals(round($expectedProfitMargin, 2), round((float)$report->profit_margin, 2), "Profit margin does not match");
        $this->assertEquals(round($expectedFinanceCost, 2), round((float)$report->finance_cost, 2), "Finance cost does not match");
        $this->assertEquals(round($expectedNetProfit, 2), round((float)$report->net_profit, 2), "Net profit does not match");
        
        echo "Monthly report verification successful!\n";
        echo "  Expected Total Sales: {$expectedTotalSales} ✓\n";
        echo "  Expected Total Revenue: \${$expectedTotalRevenue} ✓\n";
        echo "  Expected Total Profit: \${$expectedTotalProfit} ✓\n";
        echo "  Expected Finance Cost: \${$expectedFinanceCost} ✓\n";
        echo "  Expected Net Profit: \${$expectedNetProfit} ✓\n";
    }
    
    /**
     * Clean up all test data
     */
    private function cleanupAllTestData(): void
    {
        // Clean up monthly report
        $monthlyReports = MonthlySalesReport::where('year', 2025)->where('month', 6)->get();
        foreach ($monthlyReports as $report) {
            $report->delete();
        }
        
        // Clean up finance records
        $startDate = Carbon::createFromDate(2025, 6, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $financeRecords = FinanceRecord::whereBetween('record_date', [$startDate->toDateString(), $endDate->toDateString()])->get();
        foreach ($financeRecords as $record) {
            $record->delete();
        }
        
        // Clean up daily reports
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
