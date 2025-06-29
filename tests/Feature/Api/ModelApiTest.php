<?php

namespace Tests\Feature\Api;

use App\Models\Make;
use App\Models\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelApiTest extends TestCase
{
    use RefreshDatabase;

    // Predefined test users (same as SupabaseAuthTest and MakeApiTest)
    protected array $manager = ['email' => 'farman@test.com', 'password' => 'password123', 'name' => 'Manager User', 'role' => 'Manager'];
    protected array $user = ['email' => 'user@test.com', 'password' => 'password123', 'name' => 'Regular User', 'role' => 'User'];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users directly in the database (not via Supabase API)
        foreach ([$this->manager, $this->user] as $account) {
            // Create user with a UUID that mimics Supabase format
            $userId = (string) \Illuminate\Support\Str::uuid();
            
            // Insert directly into the local database
            DB::table('users')->updateOrInsert(
                ['email' => $account['email']], 
                [
                    'id' => $userId,
                    'email' => $account['email'],
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Helper to sign in and return an access token for a given account.
     */
    protected function getAccessToken(array $account): string
    {
        $response = $this->postJson('/bcms/auth/signin', [
            'email' => $account['email'],
            'password' => $account['password'],
        ]);
        $response->assertStatus(200);
        return $response->json('access_token');
    }

    /**
     * Helper method to create a model with a make
     */
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
        // Create test models with makes
        $this->createModelWithMake('Model 1');
        $this->createModelWithMake('Model 2');
        $this->createModelWithMake('Model 3');

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/models');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
        
        // Check that the response includes make relationship
        $responseData = $response->json();
        $this->assertArrayHasKey('make', $responseData[0]);
    }

    public function test_manager_can_create_a_model()
    {
        $make = Make::factory()->create();
        $modelData = [
            'name' => 'Test Model',
            'make_id' => $make->id
        ];

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/models', $modelData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Test Model'])
                 ->assertJsonStructure(['make']); // Should include make relationship
        $this->assertDatabaseHas('models', $modelData);
    }

    public function test_manager_can_get_a_single_model()
    {
        $model = $this->createModelWithMake();

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/models/' . $model->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $model->name])
                 ->assertJsonStructure(['make']); // Should include make relationship
    }

    public function test_manager_can_update_a_model()
    {
        $model = $this->createModelWithMake();
        $newMake = Make::factory()->create();
        $updatedData = [
            'name' => 'Updated Model Name',
            'make_id' => $newMake->id
        ];

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/bcms/models/' . $model->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Model Name'])
                 ->assertJsonStructure(['make']); // Should include make relationship
        $this->assertDatabaseHas('models', $updatedData);
    }

    public function test_manager_can_delete_a_model()
    {
        $model = $this->createModelWithMake();

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/bcms/models/' . $model->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('models', ['id' => $model->id]);
    }

    // Test for User
    public function test_user_can_get_all_models()
    {
        // Create test models with makes
        $this->createModelWithMake('Model 1');
        $this->createModelWithMake('Model 2');
        $this->createModelWithMake('Model 3');

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/models');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
        
        // Check that the response includes make relationship
        $responseData = $response->json();
        $this->assertArrayHasKey('make', $responseData[0]);
    }

    public function test_user_can_create_a_model()
    {
        $make = Make::factory()->create();
        $modelData = [
            'name' => 'User Test Model',
            'make_id' => $make->id
        ];

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/models', $modelData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'User Test Model'])
                 ->assertJsonStructure(['make']); // Should include make relationship
        $this->assertDatabaseHas('models', $modelData);
    }

    public function test_user_can_get_a_single_model()
    {
        $model = $this->createModelWithMake();

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/models/' . $model->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $model->name])
                 ->assertJsonStructure(['make']); // Should include make relationship
    }

    public function test_user_can_update_a_model()
    {
        $model = $this->createModelWithMake();
        $newMake = Make::factory()->create();
        $updatedData = [
            'name' => 'User Updated Model',
            'make_id' => $newMake->id
        ];

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/bcms/models/' . $model->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'User Updated Model'])
                 ->assertJsonStructure(['make']); // Should include make relationship
        $this->assertDatabaseHas('models', $updatedData);
    }

    public function test_user_can_delete_a_model()
    {
        $model = $this->createModelWithMake();

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/bcms/models/' . $model->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('models', ['id' => $model->id]);
    }

    // Test for Unauthenticated Access
    public function test_unauthenticated_user_cannot_access_models_endpoints()
    {
        $this->getJson('/bcms/models')->assertStatus(401);
        
        $make = Make::factory()->create();
        $this->postJson('/bcms/models', [
            'name' => 'No Auth Model',
            'make_id' => $make->id
        ])->assertStatus(401);
        
        $model = $this->createModelWithMake();
        $this->getJson('/bcms/models/' . $model->id)->assertStatus(401);
        $this->putJson('/bcms/models/' . $model->id, [
            'name' => 'No Auth Update',
            'make_id' => $make->id
        ])->assertStatus(401);
        $this->deleteJson('/bcms/models/' . $model->id)->assertStatus(401);
    }
    
    // Test for validation
    public function test_create_model_requires_name_and_make_id()
    {
        // Get manager token
        $token = $this->getAccessToken($this->manager);

        // Test missing name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/models', ['make_id' => Make::factory()->create()->id]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('name');

        // Test missing make_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/models', ['name' => 'Test Model']);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('make_id');

        // Test invalid make_id (non-existent UUID)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/models', [
            'name' => 'Test Model',
            'make_id' => '550e8400-e29b-41d4-a716-446655440000' // Valid UUID format but doesn't exist
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('make_id');
    }
}
