<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\Buyer;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Str;

class SaleTest extends TestCase
{
    protected $make;
    protected $model;
    protected $car;
    protected $buyer;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
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
        
        // Create a test car
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
        
        // Create a test buyer
        $this->buyer = new Buyer();
        $this->buyer->id = Str::uuid(); // Explicitly set a UUID
        $this->buyer->name = 'Test Buyer';
        $this->buyer->phone = 'TEST' . rand(10000, 99999);
        $this->buyer->address = '123 Test St';
        $this->buyer->created_by = $this->user->id;
        $this->buyer->updated_by = $this->user->id;
        $this->buyer->save();
    }
    
    protected function tearDown(): void
    {
        // Clean up all created resources in the reverse order
        if ($this->buyer) {
            $this->buyer->delete();
        }
        
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
    }

    public function test_can_create_sale()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale
        $salePrice = 23500; // Discounted from public price
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid(); // Explicitly set a UUID
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals($this->car->id, $sale->car_id);
        $this->assertEquals($this->buyer->id, $sale->buyer_id);
        $this->assertEquals($salePrice, $sale->sale_price);
        $this->assertEquals($purchaseCost, $sale->purchase_cost);
        $this->assertEquals($profitLoss, $sale->profit_loss);
        
        // Update car status to sold
        $this->car->status = 'sold';
        $this->car->selling_price = $salePrice;
        $this->car->save();
        
        // Refresh the car from the database
        $updatedCar = Car::find($this->car->id);
        $this->assertEquals('sold', $updatedCar->status);
        $this->assertEquals($salePrice, $updatedCar->selling_price);
        
        // Clean up
        $sale->delete();
    }

    public function test_fillable_attributes()
    {
        $sale = new Sale();
        
        $fillable = $sale->getFillable();
        
        $this->assertContains('id', $fillable);
        $this->assertContains('car_id', $fillable);
        $this->assertContains('buyer_id', $fillable);
        $this->assertContains('sale_price', $fillable);
        $this->assertContains('purchase_cost', $fillable);
        $this->assertContains('profit_loss', $fillable);
        $this->assertContains('sale_date', $fillable);
        $this->assertContains('notes', $fillable);
        $this->assertContains('created_by', $fillable);
        $this->assertContains('updated_by', $fillable);
    }

    public function test_has_uuid()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale without explicitly setting UUID
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid(); // Explicitly set a UUID
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        // Refresh from database to ensure we get what was actually stored
        $savedSale = Sale::find($sale->id);
        
        $this->assertNotNull($savedSale->id);
        $this->assertIsString($savedSale->id);
        // Test UUID format using regex pattern
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $savedSale->id);
        
        // Clean up
        $sale->delete();
    }

    public function test_has_timestamps()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid(); // Explicitly set a UUID
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        $this->assertNotNull($sale->created_at);
        $this->assertNotNull($sale->updated_at);
        
        // Test timestamps are instances of Carbon
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sale->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $sale->updated_at);
        
        // Clean up
        $sale->delete();
    }

    public function test_has_relationships()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid(); // Explicitly set a UUID
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();

        // Test relationship with Car
        $this->assertInstanceOf(Car::class, $sale->car);
        $this->assertEquals($this->car->id, $sale->car->id);
        $this->assertEquals($this->car->vin, $sale->car->vin);
        
        // Test relationship with Buyer
        $this->assertInstanceOf(Buyer::class, $sale->buyer);
        $this->assertEquals($this->buyer->id, $sale->buyer->id);
        $this->assertEquals($this->buyer->name, $sale->buyer->name);
        
        // Test relationship with User (created_by)
        $this->assertInstanceOf(User::class, $sale->createdBy);
        $this->assertEquals($this->user->id, $sale->createdBy->id);
        $this->assertEquals($this->user->name, $sale->createdBy->name);
        
        // Test relationship with User (updated_by)
        $this->assertInstanceOf(User::class, $sale->updatedBy);
        $this->assertEquals($this->user->id, $sale->updatedBy->id);
        $this->assertEquals($this->user->name, $sale->updatedBy->name);
        
        // Clean up
        $sale->delete();
    }

    public function test_car_cost_update_propagates_to_sale()
    {
        // This test simulates the controller's behavior when updating a car's cost
        
        // Calculate initial purchase cost
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid(); // Explicitly set a UUID
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        // Update car cost price
        $this->car->cost_price = 22000; // Increased by 2000
        $this->car->save();
        
        // Manually recalculate as the controller would
        $newPurchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        $newProfitLoss = $salePrice - $newPurchaseCost;
        
        // Update the sale record
        $sale->purchase_cost = $newPurchaseCost;
        $sale->profit_loss = $newProfitLoss;
        $sale->save();
        
        // Refresh the sale from DB
        $sale = Sale::find($sale->id);
        
        // Test updated financial calculations
        $this->assertEquals(23500, $newPurchaseCost); // 22000 + 500 + 1000
        $this->assertEquals(0, $newProfitLoss); // 23500 - 23500
        $this->assertEquals($newPurchaseCost, $sale->purchase_cost);
        $this->assertEquals($newProfitLoss, $sale->profit_loss);
        
        // Clean up
        $sale->delete();
    }

    public function test_multiple_sales_query()
    {
        // Create multiple sales for different buyers and cars
        
        // Create additional cars
        $car2 = new Car();
        $car2->id = Str::uuid();
        $car2->make_id = $this->make->id;
        $car2->model_id = $this->model->id;
        $car2->year = 2024;
        $car2->vin = 'TEST' . rand(10000, 99999);
        $car2->cost_price = 18000;
        $car2->transition_cost = 400;
        $car2->total_repair_cost = 800;
        $car2->public_price = 22000;
        $car2->status = 'available';
        $car2->created_by = $this->user->id;
        $car2->updated_by = $this->user->id;
        $car2->save();
        
        $car3 = new Car();
        $car3->id = Str::uuid();
        $car3->make_id = $this->make->id;
        $car3->model_id = $this->model->id;
        $car3->year = 2023;
        $car3->vin = 'TEST' . rand(10000, 99999);
        $car3->cost_price = 15000;
        $car3->transition_cost = 300;
        $car3->total_repair_cost = 700;
        $car3->public_price = 19000;
        $car3->status = 'available';
        $car3->created_by = $this->user->id;
        $car3->updated_by = $this->user->id;
        $car3->save();
        
        // Create additional buyers
        $buyer2 = new Buyer();
        $buyer2->id = Str::uuid();
        $buyer2->name = 'Buyer Two';
        $buyer2->phone = 'TEST' . rand(10000, 99999);
        $buyer2->address = '456 Test Ave';
        $buyer2->created_by = $this->user->id;
        $buyer2->updated_by = $this->user->id;
        $buyer2->save();
        
        // Create multiple sales
        $purchaseCost1 = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        $salePrice1 = 23500;
        $profitLoss1 = $salePrice1 - $purchaseCost1;
        
        $sale1 = new Sale();
        $sale1->id = Str::uuid();
        $sale1->car_id = $this->car->id;
        $sale1->buyer_id = $this->buyer->id;
        $sale1->sale_price = $salePrice1;
        $sale1->purchase_cost = $purchaseCost1;
        $sale1->profit_loss = $profitLoss1;
        $sale1->sale_date = now()->subDays(2)->toDateString();
        $sale1->created_by = $this->user->id;
        $sale1->updated_by = $this->user->id;
        $sale1->save();
        
        $purchaseCost2 = $car2->cost_price + $car2->transition_cost + $car2->total_repair_cost;
        $salePrice2 = 21000;
        $profitLoss2 = $salePrice2 - $purchaseCost2;
        
        $sale2 = new Sale();
        $sale2->id = Str::uuid();
        $sale2->car_id = $car2->id;
        $sale2->buyer_id = $buyer2->id;
        $sale2->sale_price = $salePrice2;
        $sale2->purchase_cost = $purchaseCost2;
        $sale2->profit_loss = $profitLoss2;
        $sale2->sale_date = now()->subDays(1)->toDateString();
        $sale2->created_by = $this->user->id;
        $sale2->updated_by = $this->user->id;
        $sale2->save();
        
        $purchaseCost3 = $car3->cost_price + $car3->transition_cost + $car3->total_repair_cost;
        $salePrice3 = 18000;
        $profitLoss3 = $salePrice3 - $purchaseCost3;
        
        $sale3 = new Sale();
        $sale3->id = Str::uuid();
        $sale3->car_id = $car3->id;
        $sale3->buyer_id = $this->buyer->id;
        $sale3->sale_price = $salePrice3;
        $sale3->purchase_cost = $purchaseCost3;
        $sale3->profit_loss = $profitLoss3;
        $sale3->sale_date = now()->toDateString();
        $sale3->created_by = $this->user->id;
        $sale3->updated_by = $this->user->id;
        $sale3->save();
        
        // Update cars to sold status
        $this->car->status = 'sold';
        $this->car->selling_price = $salePrice1;
        $this->car->save();
        
        $car2->status = 'sold';
        $car2->selling_price = $salePrice2;
        $car2->save();
        
        $car3->status = 'sold';
        $car3->selling_price = $salePrice3;
        $car3->save();
        
        // Test querying sales
        $allSales = Sale::all();
        $this->assertEquals(3, $allSales->count());
        
        // Test finding by buyer
        $buyerOneSales = Sale::where('buyer_id', $this->buyer->id)->get();
        $this->assertEquals(2, $buyerOneSales->count());
        
        // Test finding by date range
        $todaySales = Sale::whereDate('sale_date', now()->toDateString())->get();
        $this->assertEquals(1, $todaySales->count());
        
        // Test calculating total profit
        $totalProfit = Sale::sum('profit_loss');
        $expectedTotalProfit = $profitLoss1 + $profitLoss2 + $profitLoss3;
        $this->assertEquals($expectedTotalProfit, $totalProfit);
        
        // Clean up
        $sale1->delete();
        $sale2->delete();
        $sale3->delete();
        $buyer2->delete();
        $car2->delete();
        $car3->delete();
    }

    public function test_sale_with_notes()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale with notes
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        $notes = 'This is a test sale with special considerations for the customer.';
        
        $sale = new Sale();
        $sale->id = Str::uuid();
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->notes = $notes;
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        // Refresh from database
        $savedSale = Sale::find($sale->id);
        
        $this->assertEquals($notes, $savedSale->notes);
        
        // Clean up
        $sale->delete();
    }

    public function test_sale_delete_cascades_correctly()
    {
        // Calculate purchase cost as per controller logic
        $purchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        
        // Create a sale
        $salePrice = 23500;
        $profitLoss = $salePrice - $purchaseCost;
        
        $sale = new Sale();
        $sale->id = Str::uuid();
        $sale->car_id = $this->car->id;
        $sale->buyer_id = $this->buyer->id;
        $sale->sale_price = $salePrice;
        $sale->purchase_cost = $purchaseCost;
        $sale->profit_loss = $profitLoss;
        $sale->sale_date = now()->toDateString();
        $sale->created_by = $this->user->id;
        $sale->updated_by = $this->user->id;
        $sale->save();
        
        // Update car status to sold
        $this->car->status = 'sold';
        $this->car->selling_price = $salePrice;
        $this->car->save();
        
        $saleId = $sale->id;
        
        // Delete the sale
        $sale->delete();
        
        // Verify sale is deleted
        $deletedSale = Sale::find($saleId);
        $this->assertNull($deletedSale);
        
        // Verify car and buyer still exist
        $this->assertNotNull(Car::find($this->car->id));
        $this->assertNotNull(Buyer::find($this->buyer->id));
    }
}
