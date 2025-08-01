<?php

namespace Tests\Feature\Api;

use App\Models\Car;
use App\Models\Make;
use App\Models\Model as CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class CarApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected $managerToken;
    protected $userToken;
    protected $manager;
    protected $user;
    protected $make;
    protected $model;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->manager = User::factory()->create([
            'email' => 'test-manager@example.com',
            'role' => 'Manager',
            'name' => 'Test Manager'
        ]);
        
        $this->user = User::factory()->create([
            'email' => 'test-user@example.com', 
            'role' => 'User',
            'name' => 'Test User'
        ]);
        
        // Create a make and model for testing
        $this->make = Make::factory()->create();
        $this->model = CarModel::factory()->create(['make_id' => $this->make->id]);
        
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
    
    // Test for Manager
    public function test_manager_can_get_all_cars()
    {
        // Create test cars
        Car::factory()->count(3)->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/cars');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_manager_can_create_a_car()
    {        $carData = [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'cost_price' => 15000,
            'public_price' => 20000,
            'transition_cost' => 500,
            'status' => 'available',
            'vin' => 'TEST12345678901',
            'repair_items' => json_encode([
                ['description' => 'Engine repair', 'cost' => 1200],
                ['description' => 'Brake system', 'cost' => 800]
            ])
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/cars', $carData);        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'year' => 2022,
                     'cost_price' => '15000.00',
                     'public_price' => '20000.00',
                     'vin' => 'TEST12345678901'
                 ]);
        
        $this->assertDatabaseHas('cars', [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'vin' => 'TEST12345678901'
        ]);
    }

    public function test_manager_can_get_a_single_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/cars/' . $car->id);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $car->id,
                     'year' => $car->year
                 ]);
    }

    public function test_manager_can_update_a_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);
          $updatedData = [
            'year' => 2023,
            'cost_price' => 18000,
            'public_price' => 22000,
            'status' => 'available'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/cars/' . $car->id, $updatedData);        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'year' => 2023,
                     'cost_price' => '18000.00',
                     'public_price' => '22000.00'
                 ]);
          $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'year' => 2023,
            'cost_price' => 18000.00,
            'public_price' => 22000.00
        ]);
    }

    public function test_manager_can_delete_a_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/cars/' . $car->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    }

    // Test for User
    public function test_user_can_get_all_cars()
    {
        Car::factory()->count(3)->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/cars');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_create_a_car()
    {        $carData = [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'cost_price' => 15000,
            'public_price' => 20000,
            'transition_cost' => 500,
            'status' => 'available',
            'vin' => 'USER12345678901',
            'repair_items' => json_encode([
                ['description' => 'Paint job', 'cost' => 1000],
                ['description' => 'Wheel alignment', 'cost' => 300]
            ])
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/cars', $carData);

        $response->assertStatus(201)                 ->assertJsonFragment([
                     'year' => 2022,
                     'cost_price' => '15000.00',
                     'public_price' => '20000.00',
                     'vin' => 'USER12345678901'
                 ]);
        
        $this->assertDatabaseHas('cars', [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'vin' => 'USER12345678901'
        ]);
    }

    public function test_user_can_get_a_single_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/cars/' . $car->id);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $car->id,
                     'year' => $car->year
                 ]);
    }

    public function test_user_can_update_a_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);
          $updatedData = [
            'year' => 2024,
            'cost_price' => 19000,
            'public_price' => 23000,
            'status' => 'available'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/cars/' . $car->id, $updatedData);

        $response->assertStatus(200)                 ->assertJsonFragment([
                     'year' => 2024,
                     'cost_price' => '19000.00',
                     'public_price' => '23000.00'
                 ]);
          $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'year' => 2024,
            'cost_price' => 19000.00,
            'public_price' => 23000.00
        ]);
    }

    public function test_user_can_delete_a_car()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/cars/' . $car->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    }    // Test for Unauthenticated Access
    public function test_unauthenticated_user_cannot_access_protected_car_endpoints()
    {
        // Unauthenticated users CAN access the index (list) endpoint
        $this->getJson('/bcms/cars')->assertStatus(200);
          $carData = [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'cost_price' => 15000,
            'public_price' => 20000,
            'status' => 'available',
            'vin' => 'NOAUTH12345678901'
        ];
        
        // But they CANNOT create new cars
        $this->postJson('/bcms/cars', $carData)->assertStatus(401);
        
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);
        
        // They CAN view single car details
        $this->getJson('/bcms/cars/' . $car->id)->assertStatus(200);
        
        // But they CANNOT update or delete cars
        $this->putJson('/bcms/cars/' . $car->id, ['year' => 2025])->assertStatus(401);
        $this->deleteJson('/bcms/cars/' . $car->id)->assertStatus(401);
    }
    
    // Test for validation
    public function test_car_create_validates_required_fields()
    {        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/cars', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'make_id', 'model_id', 'year', 'cost_price', 
                     'public_price', 'status', 'vin'
                 ]);
    }
    
    public function test_car_create_validates_field_types()
    {        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/cars', [
            'make_id' => 'not-a-uuid',
            'model_id' => 'not-a-uuid',
            'year' => 'not-a-number',
            'cost_price' => 'not-a-number',
            'public_price' => 'not-a-number',
            'status' => 'invalid-status',
            'vin' => 'short'
        ]);        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'make_id', 'model_id', 'year', 'cost_price', 
                     'public_price', 'status', 'vin'
                 ]);
    }
    
    public function test_car_create_validates_unique_vin()
    {
        // Create a car with a specific VIN
        $existingCar = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'vin' => 'DUPLICATE1234567'
        ]);
        
        // Try to create another car with the same VIN
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,        ])->postJson('/bcms/cars', [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'cost_price' => 15000,
            'public_price' => 20000,
            'status' => 'available',
            'vin' => 'DUPLICATE1234567'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['vin']);
    }
    
    // Test for public access to the index endpoint
    public function test_anyone_can_list_cars()
    {
        // Create test cars - mix of available and sold
        Car::factory()->count(3)->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'available'
        ]);
        
        Car::factory()->count(2)->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'sold'
        ]);

        // Make request without any authentication token
        $response = $this->getJson('/bcms/cars');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        
        // Should only show available cars (3), not sold cars (2)
        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(3, $response->json('meta.total'));
        
        // Verify all returned cars have 'available' status
        foreach ($response->json('data') as $car) {
            $this->assertEquals('available', $car['status']);
        }
    }

    public function test_anyone_can_view_public_car_details()
    {
        // Create a test car with sensitive data
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'cost_price' => 15000,
            'transition_cost' => 500,
            'total_repair_cost' => 1000,
            'selling_price' => 18000,
            'public_price' => 20000,
            'status' => 'available',
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Make request without any authentication token
        $response = $this->getJson("/bcms/cars/{$car->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'year', 'vin', 'public_price', 'status', 'color', 'mileage', 'description',
                     'make' => ['id', 'name'],
                     'model' => ['id', 'name']
                 ]);

        // Verify sensitive data is NOT included
        $response->assertJsonMissing([
            'cost_price',
            'transition_cost',
            'total_repair_cost',
            'selling_price',
            'created_by',
            'updated_by',
            'sold_by',
            'repair_items'
        ]);

        // Verify only public data is included
        $response->assertJson([
            'id' => $car->id,
            'year' => $car->year,
            'vin' => $car->vin,
            'public_price' => $car->public_price,
            'status' => $car->status
        ]);
    }

    public function test_sold_cars_are_not_accessible_through_public_endpoints()
    {
        // Create a test car with sold status
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'sold',
            'public_price' => 20000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Make request without any authentication token to public endpoint
        $response = $this->getJson("/bcms/cars/{$car->id}");

        // Should return 404 for sold cars
        $response->assertStatus(404)
                 ->assertJson(['message' => 'Car not found or not available']);
    }

    public function test_authenticated_users_can_access_full_car_details()
    {
        // Create a test car with sensitive data
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'cost_price' => 15000,
            'transition_cost' => 500,
            'total_repair_cost' => 1000,
            'selling_price' => 18000,
            'public_price' => 20000,
            'status' => 'available',
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Make request with manager token to admin endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars/{$car->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'year', 'vin', 'cost_price', 'transition_cost', 'total_repair_cost',
                     'selling_price', 'public_price', 'status', 'created_by', 'updated_by',
                     'make' => ['id', 'name'],
                     'model' => ['id', 'name'],
                     'created_by' => ['id', 'name', 'email', 'role'],
                     'updated_by' => ['id', 'name', 'email', 'role']
                 ]);

        // Verify sensitive data IS included for authenticated users
        $response->assertJson([
            'id' => $car->id,
            'cost_price' => $car->cost_price,
            'transition_cost' => $car->transition_cost,
            'total_repair_cost' => $car->total_repair_cost,
            'selling_price' => $car->selling_price,
            'public_price' => $car->public_price
        ]);
    }

    public function test_authenticated_users_can_access_sold_cars_through_admin_endpoints()
    {
        // Create a test car with sold status
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'sold',
            'cost_price' => 15000,
            'transition_cost' => 500,
            'total_repair_cost' => 1000,
            'selling_price' => 18000,
            'public_price' => 20000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id
        ]);

        // Make request with manager token to admin endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson("/bcms/admin/cars/{$car->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'year', 'vin', 'cost_price', 'transition_cost', 'total_repair_cost',
                     'selling_price', 'public_price', 'status', 'created_by', 'updated_by',
                     'make' => ['id', 'name'],
                     'model' => ['id', 'name'],
                     'created_by' => ['id', 'name', 'email', 'role'],
                     'updated_by' => ['id', 'name', 'email', 'role']
                 ]);

        // Verify sold car data is accessible to authenticated users
        $response->assertJson([
            'id' => $car->id,
            'status' => 'sold',
            'cost_price' => $car->cost_price,
            'selling_price' => $car->selling_price
        ]);
    }
      // Test for total_repair_cost calculation
    public function test_total_repair_cost_calculation_is_accurate()
    {
        $repairItems = [
            ['description' => 'Engine repair', 'cost' => 1200],
            ['description' => 'Brake system', 'cost' => 800],
            ['description' => 'Windshield replacement', 'cost' => 500]
        ];
        
        $expectedTotal = 2500; // Sum of all repair costs
        
        $carData = [
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'year' => 2022,
            'cost_price' => 15000,
            'public_price' => 20000,
            'status' => 'available',
            'vin' => 'REPAIR12345678901',
            'repair_items' => json_encode($repairItems)
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/cars', $carData);

        $response->assertStatus(201);
        
        // Check that the calculated total_repair_cost matches our expected total
        $this->assertEquals($expectedTotal, $response->json('total_repair_cost'));
    }

    /**
     * Test the complete car sales process endpoint
     */
    public function test_car_sales_endpoint()
    {
        // Create a test car
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'available',
            'cost_price' => 20000,
            'public_price' => 25000,
            'transition_cost' => 500,
            'repair_items' => json_encode([
                ['description' => 'Oil change', 'cost' => 50],
                ['description' => 'Brake pads', 'cost' => 200]
            ])
        ]);

        $saleData = [
            'buyer_name' => 'John Doe',
            'buyer_phone' => '+1234567890',
            'buyer_address' => '123 Main St, City, State',
            'sale_price' => 24000,
            'sale_date' => now()->format('Y-m-d'),
            'notes' => 'Test sale via API'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson("/bcms/cars/{$car->id}/sell", $saleData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'sale' => [
                    'id', 'car_id', 'buyer_id', 'sale_price', 'purchase_cost', 
                    'profit_loss', 'sale_date', 'notes', 'created_by', 'updated_by',
                    'car' => ['id', 'make', 'model'],
                    'buyer' => ['id', 'name', 'phone']
                ],
                'buyer' => ['id', 'name', 'phone', 'address'],
                'car' => ['id', 'status', 'selling_price', 'make', 'model'],
                'financial_summary' => [
                    'sale_price', 'purchase_cost', 'profit_loss', 'profit_margin',
                    'cost_breakdown' => [
                        'base_cost', 'transition_cost', 'repair_cost', 'total_purchase_cost'
                    ],
                    'repair_items'
                ]
            ]);

        // Verify the sale was created correctly
        $this->assertDatabaseHas('sale', [
            'car_id' => $car->id,
            'sale_price' => 24000,
            'sale_date' => $saleData['sale_date']
        ]);

        // Verify the buyer was created
        $this->assertDatabaseHas('buyer', [
            'name' => 'John Doe',
            'phone' => '+1234567890'
        ]);

        // Verify the car status was updated
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'status' => 'sold',
            'selling_price' => 24000
        ]);

        // Verify financial calculations
        $responseData = $response->json();
        $expectedPurchaseCost = 20000 + 500 + 50 + 200; // cost_price + transition_cost + repair_costs
        $expectedProfitLoss = 24000 - $expectedPurchaseCost;
        
        $this->assertEquals($expectedPurchaseCost, $responseData['financial_summary']['purchase_cost']);
        $this->assertEquals($expectedProfitLoss, $responseData['financial_summary']['profit_loss']);
        $this->assertEquals(24000, $responseData['financial_summary']['sale_price']);
        
        // Verify detailed cost breakdown
        $costBreakdown = $responseData['financial_summary']['cost_breakdown'];
        $this->assertEquals(20000, $costBreakdown['base_cost']);
        $this->assertEquals(500, $costBreakdown['transition_cost']);
        $this->assertEquals(250, $costBreakdown['repair_cost']); // 50 + 200
        $this->assertEquals($expectedPurchaseCost, $costBreakdown['total_purchase_cost']);
        
        // Verify repair items are included
        $this->assertIsArray($responseData['financial_summary']['repair_items']);
        $this->assertCount(2, $responseData['financial_summary']['repair_items']);
        
        // Verify that reports were automatically generated
        $this->assertDatabaseHas('dailysalesreport', [
            'report_date' => $saleData['sale_date'],
            'total_sales' => 1,
            'total_revenue' => 24000,
            'total_profit' => $expectedProfitLoss
        ]);
        
        $dateObj = \Carbon\Carbon::parse($saleData['sale_date']);
        $this->assertDatabaseHas('monthlysalesreport', [
            'year' => $dateObj->year,
            'month' => $dateObj->month,
            'total_sales' => 1,
            'total_revenue' => 24000,
            'total_profit' => $expectedProfitLoss
        ]);
        
        $this->assertDatabaseHas('yearlysalesreport', [
            'year' => $dateObj->year,
            'total_sales' => 1,
            'total_revenue' => 24000,
            'total_profit' => $expectedProfitLoss
        ]);
    }

    /**
     * Test car sales endpoint with already sold car
     */
    public function test_car_sales_endpoint_already_sold()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'sold'
        ]);

        $saleData = [
            'buyer_name' => 'John Doe',
            'buyer_phone' => '+1234567890',
            'sale_price' => 24000,
            'sale_date' => now()->format('Y-m-d')
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson("/bcms/cars/{$car->id}/sell", $saleData);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Failed to process car sale. Car is already sold.']);
    }

    /**
     * Test car sales endpoint validation
     */
    public function test_car_sales_endpoint_validation()
    {
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson("/bcms/cars/{$car->id}/sell", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['buyer_name', 'buyer_phone', 'sale_price', 'sale_date']);
    }
}
