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
        // Create test cars
        Car::factory()->count(3)->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);

        // Make request without any authentication token
        $response = $this->getJson('/bcms/cars');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        $this->assertCount(3, $response->json('data'));
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
}
