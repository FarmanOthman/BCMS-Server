<?php

namespace Tests\Feature\Api;

use App\Models\Make;
use App\Models\Model;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ModelApiTest extends TestCase
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
    
    // Helper method to create a model with a make
    private function createModelWithMake($modelName = 'Test Model')
    {
        $make = Make::factory()->create();
        return Model::factory()->create([
            'name' => $modelName,
            'make_id' => $make->id
        ]);
    }
    
    // Test for Manager
    public function test_manager_can_get_all_models()
    {
        // Create test models
        Model::factory()->count(3)->create();

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/models');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_manager_can_create_a_model()
    {
        $make = Make::factory()->create();
        $modelData = [
            'name' => 'Test Model',
            'make_id' => $make->id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/models', $modelData);

        $response->assertStatus(201)
                 ->assertJsonFragment($modelData);
        $this->assertDatabaseHas('models', $modelData);
    }

    public function test_manager_can_get_a_single_model()
    {
        $model = $this->createModelWithMake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->getJson('/bcms/models/' . $model->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $model->name]);
    }

    public function test_manager_can_update_a_model()
    {
        $model = $this->createModelWithMake();
        $updatedData = ['name' => 'Updated Model Name'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->putJson('/bcms/models/' . $model->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('models', ['id' => $model->id, 'name' => 'Updated Model Name']);
    }

    public function test_manager_can_delete_a_model()
    {
        $model = $this->createModelWithMake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->deleteJson('/bcms/models/' . $model->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('models', ['id' => $model->id]);
    }

    // Test for User
    public function test_user_can_get_all_models()
    {
        Model::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/models');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_user_can_create_a_model()
    {
        $make = Make::factory()->create();
        $modelData = [
            'name' => 'User Test Model',
            'make_id' => $make->id
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/bcms/models', $modelData);

        $response->assertStatus(201)
                 ->assertJsonFragment($modelData);
        $this->assertDatabaseHas('models', $modelData);
    }

    public function test_user_can_get_a_single_model()
    {
        $model = $this->createModelWithMake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/bcms/models/' . $model->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $model->name]);
    }

    public function test_user_can_update_a_model()
    {
        $model = $this->createModelWithMake();
        $updatedData = ['name' => 'User Updated Model'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->putJson('/bcms/models/' . $model->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('models', ['id' => $model->id, 'name' => 'User Updated Model']);
    }

    public function test_user_can_delete_a_model()
    {
        $model = $this->createModelWithMake();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->deleteJson('/bcms/models/' . $model->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('models', ['id' => $model->id]);
    }

    // Test for Unauthenticated Access
    public function test_unauthenticated_user_cannot_access_models_endpoints()
    {
        $this->getJson('/bcms/models')->assertStatus(401);
        
        $make = Make::factory()->create();
        $modelData = ['name' => 'No Auth Model', 'make_id' => $make->id];
        $this->postJson('/bcms/models', $modelData)->assertStatus(401);
        
        $model = $this->createModelWithMake();
        $this->getJson('/bcms/models/' . $model->id)->assertStatus(401);
        $this->putJson('/bcms/models/' . $model->id, ['name' => 'No Auth Update'])->assertStatus(401);
        $this->deleteJson('/bcms/models/' . $model->id)->assertStatus(401);
    }
    
    // Test for validation
    public function test_create_model_requires_a_name_and_make_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/models', ['name' => '']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'make_id']);
        
        $make = Make::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
        ])->postJson('/bcms/models', ['name' => 'Test Model']);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('make_id');
    }
}
