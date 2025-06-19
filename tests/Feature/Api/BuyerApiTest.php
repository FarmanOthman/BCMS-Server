<?php

namespace Tests\Feature\Api;

use App\Models\Buyer;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class BuyerApiTest extends TestCase
{
    use RefreshDatabase;
    
    protected $managerToken = 'manager-test-token';
    protected $userToken = 'user-test-token';
    protected $manager;
    protected $user;
    protected $supabaseServiceMock;
    
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
    
    // Test for Manager
    public function test_manager_can_get_all_buyers()
    {
        // Create test buyers
        Buyer::factory()->count(3)->create();

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/buyers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_manager_can_create_a_buyer()
    {
        $buyerData = [
            'name' => 'Test Buyer',
            'phone' => '1234567890',
            'address' => '123 Test Street'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/buyers', $buyerData);

        $response->assertStatus(201)
                 ->assertJsonFragment($buyerData);
        $this->assertDatabaseHas('buyer', $buyerData);
    }

    public function test_manager_can_get_a_single_buyer()
    {
        $buyer = Buyer::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/buyers/' . $buyer->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $buyer->name]);
    }

    public function test_manager_can_update_a_buyer()
    {
        $buyer = Buyer::factory()->create();
        $updatedData = [
            'name' => 'Updated Buyer Name',
            'phone' => '9876543210'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/buyers/' . $buyer->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('buyer', [
            'id' => $buyer->id, 
            'name' => 'Updated Buyer Name',
            'phone' => '9876543210'
        ]);
    }

    public function test_manager_can_delete_a_buyer()
    {
        $buyer = Buyer::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/buyers/' . $buyer->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('buyer', ['id' => $buyer->id]);
    }

    // Test for User
    public function test_user_can_get_all_buyers()
    {
        Buyer::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/buyers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['page', 'limit', 'total', 'pages']
                 ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_create_a_buyer()
    {
        $buyerData = [
            'name' => 'User Test Buyer',
            'phone' => '1234567890',
            'address' => '123 User Test Street'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/buyers', $buyerData);

        $response->assertStatus(201)
                 ->assertJsonFragment($buyerData);
        $this->assertDatabaseHas('buyer', $buyerData);
    }

    public function test_user_can_get_a_single_buyer()
    {
        $buyer = Buyer::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/buyers/' . $buyer->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $buyer->name]);
    }

    public function test_user_can_update_a_buyer()
    {
        $buyer = Buyer::factory()->create();
        $updatedData = [
            'name' => 'User Updated Buyer',
            'phone' => '9876543210'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/buyers/' . $buyer->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('buyer', [
            'id' => $buyer->id,
            'name' => 'User Updated Buyer',
            'phone' => '9876543210'
        ]);
    }

    public function test_user_can_delete_a_buyer()
    {
        $buyer = Buyer::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/buyers/' . $buyer->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('buyer', ['id' => $buyer->id]);
    }

    // Test for Unauthenticated Access
    public function test_unauthenticated_user_cannot_access_buyers_endpoints()
    {
        $this->getJson('/bcms/buyers')->assertStatus(401);
        $this->postJson('/bcms/buyers', [
            'name' => 'No Auth Buyer',
            'phone' => '1234567890'
        ])->assertStatus(401);
        
        $buyer = Buyer::factory()->create();
        $this->getJson('/bcms/buyers/' . $buyer->id)->assertStatus(401);
        $this->putJson('/bcms/buyers/' . $buyer->id, ['name' => 'No Auth Update'])->assertStatus(401);
        $this->deleteJson('/bcms/buyers/' . $buyer->id)->assertStatus(401);
    }
    
    // Test for validation
    public function test_create_buyer_requires_name_and_phone()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/buyers', ['name' => '']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'phone']);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/buyers', ['name' => 'Test Buyer']);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('phone');
    }
}
