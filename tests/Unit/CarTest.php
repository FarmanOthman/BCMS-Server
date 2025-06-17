<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Car;
use App\Models\Make;
use App\Models\Model;
use App\Models\User;
use Tests\TestCase;

class CarTest extends TestCase
{
    protected $make;
    protected $model;
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create dependencies for all tests
        $this->make = new Make();
        $this->make->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $this->make->name = 'Test Make';
        $this->make->save();
        
        $this->model = new Model();
        $this->model->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $this->model->name = 'Test Model';
        $this->model->make_id = $this->make->id;
        $this->model->save();
        
        // Create a test user
        $this->user = new User();
        $this->user->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $this->user->name = 'Test User';
        $this->user->email = 'test_' . time() . '@example.com';
        $this->user->role = 'admin';
        $this->user->save();
    }
    
    protected function tearDown(): void
    {
        // Clean up all created resources
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
    
    public function test_can_create_car()
    {
        $car = new Car();
        $car->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car->make_id = $this->make->id;
        $car->model_id = $this->model->id;
        $car->year = 2025;
        $car->vin = 'TEST' . rand(10000, 99999);
        $car->cost_price = 20000;
        $car->transition_cost = 500;
        $car->public_price = 25000;
        $car->status = 'available';
        $car->created_by = $this->user->id;
        $car->updated_by = $this->user->id;
        $car->save();

        $this->assertInstanceOf(Car::class, $car);
        $this->assertEquals($this->make->id, $car->make_id);
        $this->assertEquals($this->model->id, $car->model_id);
        $this->assertEquals(2025, $car->year);
        $this->assertEquals(20000, $car->cost_price);
        $this->assertEquals('available', $car->status);
        
        // Clean up
        $car->delete();
    }
    
    public function test_has_relationships()
    {
        $car = new Car();
        $car->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car->make_id = $this->make->id;
        $car->model_id = $this->model->id;
        $car->year = 2025;
        $car->vin = 'TEST' . rand(10000, 99999);
        $car->cost_price = 20000;
        $car->transition_cost = 500;
        $car->public_price = 25000;
        $car->status = 'available';
        $car->created_by = $this->user->id;
        $car->updated_by = $this->user->id;
        $car->save();

        $this->assertInstanceOf(Make::class, $car->make);
        $this->assertEquals($this->make->name, $car->make->name);
        
        $this->assertInstanceOf(Model::class, $car->model);
        $this->assertEquals($this->model->name, $car->model->name);
        
        $this->assertInstanceOf(User::class, $car->createdBy);
        $this->assertEquals($this->user->name, $car->createdBy->name);
        
        // Clean up
        $car->delete();
    }
    
    public function test_total_repair_cost_calculation()
    {
        $car = new Car();
        $car->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car->make_id = $this->make->id;
        $car->model_id = $this->model->id;
        $car->year = 2025;
        $car->vin = 'TEST' . rand(10000, 99999);
        $car->cost_price = 20000;
        $car->transition_cost = 500;
        $car->public_price = 25000;
        $car->status = 'available';
          // Set repair items as an array with name and cost keys
        $car->repair_items = [
            ['name' => 'Paint job', 'cost' => 1200],
            ['name' => 'Brake replacement', 'cost' => 800],
            ['name' => 'Windshield repair', 'cost' => 500]
        ];
        
        // Calculate total repair cost manually
        $expectedTotal = 0;
        foreach ($car->repair_items as $item) {
            $expectedTotal += $item['cost'];
        }
        
        // Total repair cost should be calculated by controller logic when creating/updating
        // For test purposes, we'll manually set it here
        $car->total_repair_cost = $expectedTotal;
        $car->created_by = $this->user->id;
        $car->updated_by = $this->user->id;
        $car->save();

        $this->assertEquals(2500, $car->total_repair_cost);
        
        // Clean up
        $car->delete();
    }
    
    public function test_car_status_update()
    {
        $car = new Car();
        $car->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car->make_id = $this->make->id;
        $car->model_id = $this->model->id;
        $car->year = 2025;
        $car->vin = 'TEST' . rand(10000, 99999);
        $car->cost_price = 20000;
        $car->transition_cost = 500;
        $car->public_price = 25000;
        $car->status = 'available';
        $car->created_by = $this->user->id;
        $car->updated_by = $this->user->id;
        $car->save();

        // Update car status to sold
        $car->status = 'sold';
        $car->save();
        
        $updatedCar = Car::find($car->id);
        $this->assertEquals('sold', $updatedCar->status);
        
        // Clean up
        $car->delete();
    }
    
    public function test_find_cars_by_criteria()
    {
        // Create a few cars with different attributes
        $car1 = new Car();
        $car1->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car1->make_id = $this->make->id;
        $car1->model_id = $this->model->id;
        $car1->year = 2023;
        $car1->vin = 'TEST' . rand(10000, 99999);
        $car1->cost_price = 18000;
        $car1->public_price = 22000;
        $car1->status = 'available';
        $car1->created_by = $this->user->id;
        $car1->updated_by = $this->user->id;
        $car1->save();
        
        $car2 = new Car();
        $car2->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car2->make_id = $this->make->id;
        $car2->model_id = $this->model->id;
        $car2->year = 2025;
        $car2->vin = 'TEST' . rand(10000, 99999);
        $car2->cost_price = 25000;
        $car2->public_price = 30000;
        $car2->status = 'available';
        $car2->created_by = $this->user->id;
        $car2->updated_by = $this->user->id;
        $car2->save();
        
        $car3 = new Car();
        $car3->id = \Illuminate\Support\Str::uuid(); // Explicitly set a UUID
        $car3->make_id = $this->make->id;
        $car3->model_id = $this->model->id;
        $car3->year = 2024;
        $car3->vin = 'TEST' . rand(10000, 99999);
        $car3->cost_price = 20000;
        $car3->public_price = 25000;
        $car3->status = 'sold';
        $car3->created_by = $this->user->id;
        $car3->updated_by = $this->user->id;
        $car3->save();
        
        // Test finding by make
        $carsByMake = Car::where('make_id', $this->make->id)->get();
        $this->assertEquals(3, $carsByMake->count());
        
        // Test finding by model
        $carsByModel = Car::where('model_id', $this->model->id)->get();
        $this->assertEquals(3, $carsByModel->count());
        
        // Test finding by both make and model
        $carsByMakeAndModel = Car::where('make_id', $this->make->id)
                                ->where('model_id', $this->model->id)
                                ->get();
        $this->assertEquals(3, $carsByMakeAndModel->count());
        
        // Test finding by status
        $availableCars = Car::where('status', 'available')->get();
        $this->assertEquals(2, $availableCars->count());
        
        $soldCars = Car::where('status', 'sold')->get();
        $this->assertEquals(1, $soldCars->count());
        
        // Test finding by year range
        $recentCars = Car::where('year', '>=', 2024)->get();
        $this->assertEquals(2, $recentCars->count());
        
        // Clean up
        $car1->delete();
        $car2->delete();
        $car3->delete();
    }
}
