<?php

namespace Tests\Feature\Api;

use App\Models\Make;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class MakeApiTest extends TestCase
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
    public function test_manager_can_get_all_makes()
    {
        // Create test makes
        Make::factory()->count(3)->create();

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/makes');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());    }

    public function test_manager_can_create_a_make()
    {
        $makeData = ['name' => 'Test Make'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/makes', $makeData);

        $response->assertStatus(201)
                 ->assertJsonFragment($makeData);
        $this->assertDatabaseHas('makes', $makeData);    }

    public function test_manager_can_get_a_single_make()
    {
        $make = Make::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/makes/' . $make->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $make->name]);    }

    public function test_manager_can_update_a_make()
    {
        $make = Make::factory()->create();
        $updatedData = ['name' => 'Updated Make Name'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/makes/' . $make->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('makes', $updatedData);    }

    public function test_manager_can_delete_a_make()
    {
        $make = Make::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/makes/' . $make->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('makes', ['id' => $make->id]);    }

    // Test for User
    public function test_user_can_get_all_makes()
    {
        Make::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,        ])->getJson('/bcms/makes');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());    }

    public function test_user_can_create_a_make()
    {
        $makeData = ['name' => 'User Test Make'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/makes', $makeData);

        $response->assertStatus(201)
                 ->assertJsonFragment($makeData);
        $this->assertDatabaseHas('makes', $makeData);    }

    public function test_user_can_get_a_single_make()
    {
        $make = Make::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/makes/' . $make->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $make->name]);    }

    public function test_user_can_update_a_make()
    {
        $make = Make::factory()->create();
        $updatedData = ['name' => 'User Updated Make'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/makes/' . $make->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('makes', $updatedData);    }

    public function test_user_can_delete_a_make()
    {
        $make = Make::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/makes/' . $make->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('makes', ['id' => $make->id]);    }

    // Test for Unauthenticated Access
    public function test_unauthenticated_user_cannot_access_makes_endpoints()
    {
        $this->getJson('/bcms/makes')->assertStatus(401);
        $this->postJson('/bcms/makes', ['name' => 'No Auth Make'])->assertStatus(401);
        
        $make = Make::factory()->create();
        $this->getJson('/bcms/makes/' . $make->id)->assertStatus(401);
        $this->putJson('/bcms/makes/' . $make->id, ['name' => 'No Auth Update'])->assertStatus(401);
        $this->deleteJson('/bcms/makes/' . $make->id)->assertStatus(401);    }
    
    // Test for validation
    public function test_create_make_requires_a_name()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/makes', ['name' => '']);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('name');
    }
}
