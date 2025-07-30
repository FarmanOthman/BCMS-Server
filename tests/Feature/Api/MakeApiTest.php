<?php

namespace Tests\Feature\Api;

use App\Models\Make;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MakeApiTest extends TestCase
{
    use RefreshDatabase;

    // Predefined test users (same as SupabaseAuthTest)
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
    
    // Test for Manager
    public function test_manager_can_get_all_makes()
    {
        // Create test makes
        Make::factory()->count(3)->create();

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        // Make request with manager token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/makes');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_manager_can_create_a_make()
    {
        $makeData = ['name' => 'Test Make'];

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/makes', $makeData);

        $response->assertStatus(201)
                 ->assertJsonFragment($makeData);
        $this->assertDatabaseHas('makes', $makeData);
    }

    public function test_manager_can_get_a_single_make()
    {
        $make = Make::factory()->create();

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/makes/' . $make->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $make->name]);
    }

    public function test_manager_can_update_a_make()
    {
        $make = Make::factory()->create();
        $updatedData = ['name' => 'Updated Make Name'];

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/bcms/makes/' . $make->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('makes', $updatedData);
    }

    public function test_manager_can_delete_a_make()
    {
        $make = Make::factory()->create();

        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/bcms/makes/' . $make->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('makes', ['id' => $make->id]);
    }

    // Test for User
    public function test_user_can_get_all_makes()
    {
        Make::factory()->count(3)->create();

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/makes');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_user_can_create_a_make()
    {
        $makeData = ['name' => 'User Test Make'];

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/makes', $makeData);

        $response->assertStatus(201)
                 ->assertJsonFragment($makeData);
        $this->assertDatabaseHas('makes', $makeData);
    }

    public function test_user_can_get_a_single_make()
    {
        $make = Make::factory()->create();

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/bcms/makes/' . $make->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $make->name]);
    }

    public function test_user_can_update_a_make()
    {
        $make = Make::factory()->create();
        $updatedData = ['name' => 'User Updated Make'];

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/bcms/makes/' . $make->id, $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updatedData);
        $this->assertDatabaseHas('makes', $updatedData);
    }

    public function test_user_can_delete_a_make()
    {
        $make = Make::factory()->create();

        // Get user token
        $token = $this->getAccessToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/bcms/makes/' . $make->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('makes', ['id' => $make->id]);
    }

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
        // Get manager token
        $token = $this->getAccessToken($this->manager);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/bcms/makes', ['name' => '']);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('name');
    }
}
