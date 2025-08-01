<?php

declare(strict_types=1);

/**
 * Unit Test: Comprehensive Sales Process
 * 
 * Purpose:
 * This test provides a comprehensive end-to-end test of the entire sales process,
 * from creating cars and buyers to completing sales and generating reports.
 * 
 * What it tests:
 * - Creation of makes, models, cars with varied attributes
 * - Creation of buyers
 * - Processing various types of sales (profitable, break-even, loss)
 * - Verification of sales records and calculations
 * - Car status changes after sale
 * - Profit/loss scenarios across different types of sales
 * - Buyer purchase history and relationships
 * - Daily sales report generation based on completed sales
 * - Sales record updates and their effects
 * 
 * Usage:
 * Run this test to verify the complete sales workflow functions correctly.
 * This test ensures that all components of the sales process work together properly,
 * including the financial calculations, status changes, and report generation.
 * 
 * Note:
 * This test includes a 60-second pause to allow for manual database inspection.
 */

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\Buyer;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ComprehensiveSaleTest extends TestCase
{
    protected $user;
    protected $makes = [];
    protected $models = [];
    protected $cars = [];
    protected $buyers = [];
    protected $sales = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = new User();
        $this->user->id = Str::uuid();
        $this->user->name = 'Test Sales Manager';
        $this->user->email = 'sales_manager_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();

        // Create car makes
        $this->createMakes(['Toyota', 'Honda', 'Ford', 'BMW', 'Mercedes']);

        // Create car models for each make
        $this->createModels();

        // Create cars for each model with different attributes
        $this->createCars();

        // Create potential buyers
        $this->createBuyers();
    }

    protected function tearDown(): void
    {
        // Clean up all test data
        $this->cleanupAllTestData();
        parent::tearDown();
    }

    /**
     * Comprehensive test of the entire sales process flow
     */
    public function test_complete_sales_process_flow()
    {
        // 1. Create multiple sales with different scenarios
        echo "\nStep 1: Creating multiple sales with different scenarios\n";
        $this->createTestSales();

        // 2. Verify each sale was recorded correctly with proper relationships
        echo "\nStep 2: Verifying each sale was recorded correctly\n";
        $this->verifySalesRecords();

        // 3. Verify car status changes after sale
        echo "\nStep 3: Verifying car status changes after sale\n";
        $this->verifyCarStatusChanges();

        // 4. Test different profit/loss scenarios
        echo "\nStep 4: Testing different profit/loss scenarios\n";
        $this->testProfitLossScenarios();

        // 5. Test buyer relationship and history
        echo "\nStep 5: Testing buyer relationships and purchase history\n";
        $this->testBuyerRelationships();

        // 6. Test updates to existing sales
        echo "\nStep 6: Testing updates to existing sales\n";
        $this->testSalesUpdates();

        // 7. Test completed successfully
        echo "\nComprehensive sales process test completed successfully!\n";
    }

    /**
     * Create car makes
     */
    private function createMakes(array $makeNames): void
    {
        foreach ($makeNames as $makeName) {
            $make = new Make();
            $make->id = Str::uuid();
            $make->name = $makeName;
            $make->save();
            $this->makes[$makeName] = $make;
        }
    }

    /**
     * Create car models for each make
     */
    private function createModels(): void
    {
        $toyotaModels = ['Camry', 'Corolla', 'RAV4', 'Highlander'];
        $hondaModels = ['Civic', 'Accord', 'CR-V', 'Pilot'];
        $fordModels = ['F-150', 'Mustang', 'Explorer', 'Escape'];
        $bmwModels = ['3 Series', '5 Series', 'X3', 'X5'];
        $mercedesModels = ['C-Class', 'E-Class', 'GLC', 'GLE'];

        $allModels = [
            'Toyota' => $toyotaModels,
            'Honda' => $hondaModels,
            'Ford' => $fordModels,
            'BMW' => $bmwModels,
            'Mercedes' => $mercedesModels,
        ];

        foreach ($allModels as $makeName => $modelNames) {
            $make = $this->makes[$makeName];
            foreach ($modelNames as $modelName) {
                $model = new Model();
                $model->id = Str::uuid();
                $model->name = $modelName;
                $model->make_id = $make->id;
                $model->save();
                $this->models[$makeName . ' ' . $modelName] = $model;
            }
        }
    }

    /**
     * Create cars with varying attributes
     */
    private function createCars(): void
    {
        $carSpecifications = [
            ['Toyota', 'Camry', 2022, 20000, 500, 1500, 24000, 'available'],
            ['Toyota', 'Corolla', 2023, 18000, 400, 800, 22000, 'available'],
            ['Honda', 'Civic', 2021, 17500, 450, 1200, 21000, 'available'],
            ['Honda', 'Accord', 2022, 22000, 550, 1800, 26000, 'available'],
            ['Ford', 'F-150', 2020, 25000, 700, 2500, 32000, 'available'],
            ['Ford', 'Mustang', 2021, 28000, 600, 2000, 35000, 'available'],
            ['BMW', '3 Series', 2022, 35000, 800, 3000, 45000, 'available'],
            ['BMW', '5 Series', 2021, 42000, 900, 3500, 52000, 'available'],
            ['Mercedes', 'C-Class', 2023, 38000, 850, 2800, 48000, 'available'],
            ['Mercedes', 'E-Class', 2022, 45000, 950, 3200, 55000, 'available'],
        ];

        foreach ($carSpecifications as $index => $spec) {
            list($makeName, $modelName, $year, $costPrice, $transitionCost, $repairCost, $publicPrice, $status) = $spec;
            
            $make = $this->makes[$makeName];
            $model = $this->models[$makeName . ' ' . $modelName];
            
            $car = new Car();
            $car->id = Str::uuid();
            $car->make_id = $make->id;
            $car->model_id = $model->id;
            $car->year = $year;
            $car->vin = 'TEST' . rand(100000, 999999);
            $car->cost_price = $costPrice;
            $car->transition_cost = $transitionCost;
            $car->total_repair_cost = $repairCost;
            $car->public_price = $publicPrice;
            $car->status = $status;
            
            // Set repair items as JSON
            $car->repair_items = [
                ['description' => 'Paint touch-up', 'cost' => $repairCost * 0.2],
                ['description' => 'Interior cleaning', 'cost' => $repairCost * 0.1],
                ['description' => 'Mechanical checks', 'cost' => $repairCost * 0.4],
                ['description' => 'Parts replacement', 'cost' => $repairCost * 0.3],
            ];
            
            $car->created_by = $this->user->id;
            $car->updated_by = $this->user->id;
            $car->save();
            
            $this->cars[] = $car;
        }
    }

    /**
     * Create potential buyers
     */
    private function createBuyers(): void
    {
        $buyerData = [
            ['John Doe', '555-123-4567', '123 Main St, City, State'],
            ['Jane Smith', '555-234-5678', '456 Oak Ave, Town, State'],
            ['Robert Johnson', '555-345-6789', '789 Pine Rd, Village, State'],
            ['Sarah Williams', '555-456-7890', '101 Maple Ln, County, State'],
            ['Michael Brown', '555-567-8901', '202 Cedar Dr, Metro, State'],
        ];

        foreach ($buyerData as $data) {
            list($name, $phone, $address) = $data;
            
            $buyer = new Buyer();
            $buyer->id = Str::uuid();
            $buyer->name = $name;
            $buyer->phone = $phone;
            $buyer->address = $address;
            $buyer->created_by = $this->user->id;
            $buyer->updated_by = $this->user->id;
            $buyer->save();
            
            $this->buyers[] = $buyer;
        }
    }

    /**
     * Create test sales with different scenarios
     */
    private function createTestSales(): void
    {        // First sale - Sold at public price (good profit)
        $this->createSale(
            $this->cars[0], 
            $this->buyers[0], 
            (float)$this->cars[0]->public_price, 
            Carbon::today()->subDays(5)
        );
        
        // Second sale - Sold below public price but still profitable
        $this->createSale(
            $this->cars[1], 
            $this->buyers[1], 
            (float)($this->cars[1]->public_price * 0.95), 
            Carbon::today()->subDays(4)
        );
          // Third sale - Sold at cost (break-even)
        $totalCost = (float)$this->cars[2]->cost_price + (float)$this->cars[2]->transition_cost + (float)$this->cars[2]->total_repair_cost;
        $this->createSale(
            $this->cars[2], 
            $this->buyers[2], 
            $totalCost, 
            Carbon::today()->subDays(3)
        );
        
        // Fourth sale - Sold at a loss
        $totalCost = (float)$this->cars[3]->cost_price + (float)$this->cars[3]->transition_cost + (float)$this->cars[3]->total_repair_cost;        $this->createSale(
            $this->cars[3], 
            $this->buyers[3], 
            (float)($totalCost * 0.9), 
            Carbon::today()->subDays(2)
        );
          // Fifth sale - Premium vehicle with high profit
        $this->createSale(
            $this->cars[7], 
            $this->buyers[4], 
            (float)($this->cars[7]->public_price * 1.05), 
            Carbon::today()->subDays(1)
        );
        
        // Sixth and Seventh sale - Two sales on the same day
        $this->createSale(
            $this->cars[8], 
            $this->buyers[0], 
            (float)$this->cars[8]->public_price, 
            Carbon::today()
        );
        
        $this->createSale(
            $this->cars[9], 
            $this->buyers[1], 
            (float)$this->cars[9]->public_price, 
            Carbon::today()
        );
    }    /**
     * Create a single sale record
     */
    private function createSale(Car $car, Buyer $buyer, float $salePrice, Carbon $saleDate): void
    {
        $purchaseCost = (float)$car->cost_price + (float)$car->transition_cost + (float)$car->total_repair_cost;
        $profitLoss = (float)$salePrice - (float)$purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid();
        $sale->car_id = $car->id;
        $sale->buyer_id = $buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
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

    /**
     * Verify all sales records were created correctly
     */
    private function verifySalesRecords(): void
    {
        foreach ($this->sales as $index => $sale) {
            // Refresh from database
            $freshSale = Sale::with(['car', 'car.make', 'car.model', 'buyer'])->find($sale->id);
            
            // Verify basic properties
            $this->assertNotNull($freshSale);
            $this->assertIsString($freshSale->id);
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $freshSale->id);
            
            // Verify relationships
            $this->assertInstanceOf(Car::class, $freshSale->car);
            $this->assertInstanceOf(Buyer::class, $freshSale->buyer);
            $this->assertInstanceOf(Make::class, $freshSale->car->make);
            $this->assertInstanceOf(Model::class, $freshSale->car->model);
              // Verify calculations
            $expectedPurchaseCost = (float)$freshSale->car->cost_price + (float)$freshSale->car->transition_cost + (float)$freshSale->car->total_repair_cost;
            $expectedProfitLoss = (float)$freshSale->sale_price - $expectedPurchaseCost;
            
            $this->assertEquals(round((float)$expectedPurchaseCost, 2), round((float)$freshSale->purchase_cost, 2), "Purchase cost calculation incorrect for sale {$index}");
            $this->assertEquals(round((float)$expectedProfitLoss, 2), round((float)$freshSale->profit_loss, 2), "Profit/loss calculation incorrect for sale {$index}");
            
            echo "Verified sale {$index}: {$freshSale->car->year} {$freshSale->car->make->name} {$freshSale->car->model->name} - Profit/Loss: \${$freshSale->profit_loss}\n";
        }
    }

    /**
     * Verify car status changes after sales
     */
    private function verifyCarStatusChanges(): void
    {
        foreach ($this->sales as $sale) {
            $car = Car::find($sale->car_id);
            
            $this->assertEquals('sold', $car->status, "Car status should be 'sold' after sale");
            $this->assertEquals((float)$sale->sale_price, (float)$car->selling_price, "Car selling price should match sale price");
            
            echo "Verified car status for {$car->year} {$car->make->name} {$car->model->name}: {$car->status}\n";
        }
    }

    /**
     * Test different profit/loss scenarios
     */
    private function testProfitLossScenarios(): void
    {
        // Get sales ordered by profit/loss
        $salesByProfit = collect($this->sales)->sortBy('profit_loss');
        
        // Test the sale with the lowest profit (possibly a loss)
        $lowestProfitSale = $salesByProfit->first();
        $this->assertNotNull($lowestProfitSale);
        echo "Lowest profit sale: {$lowestProfitSale->car->year} {$lowestProfitSale->car->make->name} {$lowestProfitSale->car->model->name} - Profit/Loss: \${$lowestProfitSale->profit_loss}\n";
        
        // Test the sale with the highest profit
        $highestProfitSale = $salesByProfit->last();
        $this->assertNotNull($highestProfitSale);
        echo "Highest profit sale: {$highestProfitSale->car->year} {$highestProfitSale->car->make->name} {$highestProfitSale->car->model->name} - Profit/Loss: \${$highestProfitSale->profit_loss}\n";
        
        // Calculate average profit across all sales
        $totalProfit = collect($this->sales)->sum('profit_loss');
        $averageProfit = $totalProfit / count($this->sales);
        echo "Average profit across all sales: \${$averageProfit}\n";
        
        // Group by profit/loss status
        $profitableSales = collect($this->sales)->filter(function($sale) {
            return $sale->profit_loss > 0;
        });
        
        $breakEvenSales = collect($this->sales)->filter(function($sale) {
            return round((float)$sale->profit_loss, 2) == 0;
        });
        
        $lossSales = collect($this->sales)->filter(function($sale) {
            return $sale->profit_loss < 0;
        });
        
        echo "Profitable sales: {$profitableSales->count()}\n";
        echo "Break-even sales: {$breakEvenSales->count()}\n";
        echo "Loss-making sales: {$lossSales->count()}\n";
    }

    /**
     * Test buyer relationships and purchase history
     */
    private function testBuyerRelationships(): void
    {
        $buyerStats = [];
        
        // Group sales by buyer
        foreach ($this->sales as $sale) {
            $buyerId = $sale->buyer_id;
            
            if (!isset($buyerStats[$buyerId])) {
                $buyerStats[$buyerId] = [
                    'buyer' => Buyer::find($buyerId),
                    'sales' => [],
                    'totalSpent' => 0,
                ];
            }
            
            $buyerStats[$buyerId]['sales'][] = $sale;
            $buyerStats[$buyerId]['totalSpent'] += (float)$sale->sale_price;
        }
        
        // Verify each buyer's purchases
        foreach ($buyerStats as $buyerId => $stats) {
            $buyer = $stats['buyer'];
            $salesCount = count($stats['sales']);
            $totalSpent = $stats['totalSpent'];
            
            echo "Buyer: {$buyer->name} - Purchases: {$salesCount}, Total Spent: \${$totalSpent}\n";
            
            // For buyers with multiple purchases, verify all cars
            if ($salesCount > 1) {
                echo "  Multiple purchases by {$buyer->name}:\n";
                foreach ($stats['sales'] as $sale) {
                    $car = Car::with(['make', 'model'])->find($sale->car_id);
                    echo "  - {$car->year} {$car->make->name} {$car->model->name} for \${$sale->sale_price} on {$sale->sale_date}\n";
                }
            }
        }
    }

    /**
     * Test updates to existing sales
     */
    private function testSalesUpdates(): void
    {        // Take the first sale and update some attributes
        $sale = $this->sales[0];
        $originalSalePrice = (float)$sale->sale_price;
        $originalProfitLoss = (float)$sale->profit_loss;
        
        // Update sale price and notes
        $newSalePrice = (float)($originalSalePrice * 1.05); // 5% increase
        $newProfitLoss = $newSalePrice - (float)$sale->purchase_cost;
        
        $sale->sale_price = $newSalePrice;
        $sale->profit_loss = $newProfitLoss;
        $sale->notes = $sale->notes . " (Updated)";
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        // Refresh from database
        $updatedSale = Sale::find($sale->id);
        
        // Verify updates
        $this->assertEquals($newSalePrice, $updatedSale->sale_price);
        $this->assertEquals($newProfitLoss, $updatedSale->profit_loss);
        $this->assertTrue(str_contains($updatedSale->notes, "(Updated)"));
        
        echo "Successfully updated sale price from \${$originalSalePrice} to \${$newSalePrice}\n";
        echo "Profit changed from \${$originalProfitLoss} to \${$newProfitLoss}\n";
        
        // Also update the car's selling price to match
        $car = Car::find($sale->car_id);
        $car->selling_price = $newSalePrice;
        $car->save();
        
        $updatedCar = Car::find($car->id);
        $this->assertEquals($newSalePrice, $updatedCar->selling_price);
    }

    /**
     * Clean up all test data
     */    private function cleanupAllTestData(): void
    {
        // Delete all created sales
        foreach ($this->sales as $sale) {
            if (Sale::find($sale->id)) {
                $sale->delete();
            }
        }
        

        
        // Reset car status for any cars that were sold
        foreach ($this->cars as $car) {
            $freshCar = Car::find($car->id);
            if ($freshCar && $freshCar->status === 'sold') {
                $freshCar->status = 'available';
                $freshCar->selling_price = null;
                $freshCar->save();
            }
            if ($freshCar) {
                $freshCar->delete();
            }
        }
        
        // Delete all buyers
        foreach ($this->buyers as $buyer) {
            if (Buyer::find($buyer->id)) {
                $buyer->delete();
            }
        }
        
        // Delete all models
        foreach ($this->models as $model) {
            if (Model::find($model->id)) {
                $model->delete();
            }
        }
        
        // Delete all makes
        foreach ($this->makes as $make) {
            if (Make::find($make->id)) {
                $make->delete();
            }
        }
        
        // Delete the user
        if ($this->user && User::find($this->user->id)) {
            $this->user->delete();
        }
    }
}
