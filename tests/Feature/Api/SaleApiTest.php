<?php

namespace Tests\Feature\Api;

use App\Models\Buyer;
use App\Models\Car;
use App\Models\Make;
use App\Models\Model as CarModel;
use App\Models\Sale;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;
      protected $managerToken = 'manager-test-token';
    protected $userToken = 'user-test-token';
    protected $manager;
    protected $user;
    protected $supabaseServiceMock;
    protected $make;
    protected $model;
    protected $car;
    protected $buyer;
    
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
        
        // Create a car for testing
        $this->car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'available',
            'cost_price' => 10000,
            'transition_cost' => 500,
            'total_repair_cost' => 1500,
            'repair_items' => [
                ['description' => 'Engine repair', 'cost' => 1000],
                ['description' => 'Brake system', 'cost' => 500]
            ]
        ]);
          // Create a buyer for testing
        $this->buyer = Buyer::factory()->create();
          // Mock the SupabaseService
        $this->supabaseServiceMock = Mockery::mock(SupabaseService::class);
        $this->app->instance(SupabaseService::class, $this->supabaseServiceMock);
        
        // Setup the mock for the manager token
        $this->supabaseServiceMock->shouldReceive('getUserByAccessToken')
            ->with($this->managerToken)
            ->andReturn([
                'id' => $this->manager->id,
                'email' => $this->manager->email,
                'name' => $this->manager->name,
                'role' => 'Manager'
            ]);
              // Setup the mock for the user token
        $this->supabaseServiceMock->shouldReceive('getUserByAccessToken')
            ->with($this->userToken)
            ->andReturn([
                'id' => $this->user->id,
                'email' => $this->user->email,
                'name' => $this->user->name,
                'role' => 'User'
            ]);
            
        // Setup the mock for invalid tokens - this will handle any other token
        $this->supabaseServiceMock->shouldReceive('getUserByAccessToken')
            ->withAnyArgs()
            ->andReturnNull();
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_manager_can_get_all_sales()
    {
        // Create test sales
        Sale::factory()->count(3)->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }
      public function test_manager_can_create_a_sale()
    {
        // Login the manager
        $this->actingAs($this->manager);
        
        $saleData = [
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => 15000,
            'sale_date' => now()->format('Y-m-d'),
            'notes' => 'Test sale notes'
        ];        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', $saleData);

        // Dump response content for debugging
        if ($response->status() != 201) {
            dump($response->json());
        }

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'car_id' => $this->car->id,
                     'buyer_id' => $this->buyer->id,
                     'sale_price' => '15000.00',
                     'notes' => 'Test sale notes'
                 ]);
        
        // Check that purchase_cost is correctly calculated
        $expectedPurchaseCost = $this->car->cost_price + $this->car->transition_cost + $this->car->total_repair_cost;
        $this->assertEquals($expectedPurchaseCost, $response->json('purchase_cost'));
        
        // Check that profit_loss is correctly calculated
        $expectedProfitLoss = 15000 - $expectedPurchaseCost;
        $this->assertEquals($expectedProfitLoss, $response->json('profit_loss'));
          // Check that the car status is updated to 'sold'
        $this->assertDatabaseHas('cars', [
            'id' => $this->car->id,
            'status' => 'sold',
            'selling_price' => 15000
        ]);
    }
    
    public function test_manager_can_get_a_single_sale()
    {
        $sale = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales/' . $sale->id);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $sale->id,
                     'car_id' => $this->car->id,
                     'buyer_id' => $this->buyer->id
                 ]);
    }
      public function test_manager_can_update_a_sale()
    {
        // Login the manager
        $this->actingAs($this->manager);
        
        $sale = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => 14000
        ]);
        
        $updatedData = [
            'sale_price' => 16000,
            'notes' => 'Updated sale notes'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/sales/' . $sale->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'sale_price' => '16000.00',
                     'notes' => 'Updated sale notes'
                 ]);
          // Check that profit_loss is recalculated
        $expectedPurchaseCost = $sale->purchase_cost;
        $expectedProfitLoss = 16000 - $expectedPurchaseCost;
        $this->assertEqualsWithDelta($expectedProfitLoss, (float)$response->json('profit_loss'), 0.5, 'Profit/loss calculation has unexpected difference');
          // Check that the car's selling_price is updated
        $this->assertDatabaseHas('cars', [
            'id' => $this->car->id,
            'selling_price' => 16000
        ]);
    }
      public function test_manager_can_delete_a_sale()
    {
        // Login the manager
        $this->actingAs($this->manager);
        
        $sale = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/sales/' . $sale->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sale', ['id' => $sale->id]);
          // Check that the car status is updated back to 'available'
        $this->assertDatabaseHas('cars', [
            'id' => $this->car->id,
            'status' => 'available',
            'selling_price' => null
        ]);
    }
    
    public function test_cannot_sell_already_sold_car()
    {
        // Create a sale for the car first
        $existingSale = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id
        ]);
        
        // Update car status to sold (this should happen automatically in a real scenario)
        $this->car->update(['status' => 'sold']);
        
        // Try to create another sale for the same car
        $newSaleData = [
            'car_id' => $this->car->id,
            'buyer_id' => Buyer::factory()->create()->id,
            'sale_price' => 16000,
            'sale_date' => now()->format('Y-m-d'),
            'notes' => 'This should fail'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', $newSaleData);

        $response->assertStatus(409) // Conflict status code
                 ->assertJsonFragment([
                     'error' => 'Car is already sold.'
                 ]);
    }
    
    public function test_cannot_change_car_in_sale()
    {
        $sale = Sale::factory()->create([
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id
        ]);
        
        // Create a different car
        $anotherCar = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id
        ]);
        
        // Try to update the car_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/sales/' . $sale->id, [
            'car_id' => $anotherCar->id
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment([
                     'error' => 'Cannot change the car associated with a sale. Please create a new sale.'
                 ]);
    }
    
    public function test_regular_user_cannot_access_sales_api()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/sales');

        $response->assertStatus(403);
        
        $saleData = [
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => 15000,
            'sale_date' => now()->format('Y-m-d')
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/sales', $saleData);

        $response->assertStatus(403);
    }
    
    public function test_unauthenticated_user_cannot_access_sales_api()
    {
        $response = $this->getJson('/bcms/sales');
        $response->assertStatus(401);
        
        $saleData = [
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => 15000,
            'sale_date' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/bcms/sales', $saleData);
        $response->assertStatus(401);
    }
    
    public function test_sales_validation_rules()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'car_id', 'buyer_id', 'sale_price', 'sale_date'
                 ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', [
            'car_id' => 'not-a-uuid',
            'buyer_id' => 'not-a-uuid',
            'sale_price' => 'not-a-number',
            'sale_date' => 'not-a-date'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'car_id', 'buyer_id', 'sale_price', 'sale_date'
                 ]);
        
        // Test future date validation
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', [
            'car_id' => $this->car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => 15000,
            'sale_date' => now()->addDays(2)->format('Y-m-d') // Future date
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sale_date']);
    }
    
    public function test_sale_filters_work_correctly()
    {
        // Create cars and buyers for testing filters
        $car1 = Car::factory()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id]);
        $car2 = Car::factory()->create(['make_id' => $this->make->id, 'model_id' => $this->model->id]);
        $buyer1 = Buyer::factory()->create();
        $buyer2 = Buyer::factory()->create();
        
        // Create sales with different attributes for testing filters
        $sale1 = Sale::factory()->create([
            'car_id' => $car1->id,
            'buyer_id' => $buyer1->id,
            'sale_price' => 10000,
            'sale_date' => '2025-01-15'
        ]);
        
        $sale2 = Sale::factory()->create([
            'car_id' => $car2->id,
            'buyer_id' => $buyer1->id,
            'sale_price' => 15000,
            'sale_date' => '2025-02-20'
        ]);
        
        $sale3 = Sale::factory()->create([
            'car_id' => $car1->id,
            'buyer_id' => $buyer2->id,
            'sale_price' => 20000,
            'sale_date' => '2025-03-25'
        ]);
        
        // Test car_id filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?car_id=' . $car1->id);
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
        
        // Test buyer_id filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?buyer_id=' . $buyer1->id);
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
        
        // Test sale_date filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?sale_date=2025-02-20');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        
        // Test sale_date range filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?sale_date_from=2025-02-01&sale_date_to=2025-03-31');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
        
        // Test sale_price filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?sale_price=15000');
        
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        
        // Test sale_price range filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/sales?min_sale_price=15000&max_sale_price=20000');
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }
    
    public function test_sale_profit_loss_calculation_is_accurate()
    {
        // Create a car with specific cost values
        $car = Car::factory()->create([
            'make_id' => $this->make->id,
            'model_id' => $this->model->id,
            'status' => 'available',
            'cost_price' => 10000, // Base cost of the car
            'transition_cost' => 500, // Cost to transport/transition the car
            'repair_items' => [
                ['description' => 'Engine repair', 'cost' => 1000],
                ['description' => 'Brake system', 'cost' => 500],
                ['description' => 'Paint job', 'cost' => 800]
            ]
        ]);
        
        // Manually calculate the expected values
        $expectedTotalRepairCost = 2300; // 1000 + 500 + 800
        $expectedPurchaseCost = 10000 + 500 + 2300; // cost_price + transition_cost + total_repair_cost
        $salePrice = 15000;
        $expectedProfitLoss = $salePrice - $expectedPurchaseCost;
        
        // Create a sale using the API
        $saleData = [
            'car_id' => $car->id,
            'buyer_id' => $this->buyer->id,
            'sale_price' => $salePrice,
            'sale_date' => now()->format('Y-m-d'),
            'notes' => 'Test calculation sale'
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/sales', $saleData);
        
        // Verify the response status and structure
        $response->assertStatus(201);
        
        // Verify the calculations in the response
        $this->assertEquals($expectedPurchaseCost, (float)$response->json('purchase_cost'), 'Purchase cost calculation is incorrect');
        $this->assertEquals($expectedProfitLoss, (float)$response->json('profit_loss'), 'Profit/loss calculation is incorrect');
        
        // Verify the car status was updated correctly
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'status' => 'sold',
            'selling_price' => $salePrice
        ]);
        
        // Now test the update scenario
        $saleId = $response->json('id');
        $newSalePrice = 16500;
        $newExpectedProfitLoss = $newSalePrice - $expectedPurchaseCost;
        
        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/sales/' . $saleId, [
            'sale_price' => $newSalePrice
        ]);
        
        // Verify the update calculations
        $updateResponse->assertStatus(200);
        $this->assertEquals($expectedPurchaseCost, (float)$updateResponse->json('purchase_cost'), 'Purchase cost should remain the same after update');
        $this->assertEquals($newExpectedProfitLoss, (float)$updateResponse->json('profit_loss'), 'Updated profit/loss calculation is incorrect');
        
        // Verify the car selling price was updated
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'selling_price' => $newSalePrice
        ]);
        
        // Finally test deletion and car status reversal
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/sales/' . $saleId);
        
        $deleteResponse->assertStatus(204);
        
        // Verify the car status was reset properly
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'status' => 'available',
            'selling_price' => null
        ]);
        
        // Verify the sale record was actually deleted
        $this->assertDatabaseMissing('sale', ['id' => $saleId]);
    }
}
