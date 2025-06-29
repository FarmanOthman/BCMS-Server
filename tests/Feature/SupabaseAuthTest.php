<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupabaseAuthTest extends TestCase
{
    use RefreshDatabase;

    protected SupabaseService $supabase;

    // Predefined test users
    protected array $manager = ['email' => 'farman@test.com', 'password' => 'password123', 'name' => 'Manager User', 'role' => 'Manager'];
    protected array $user = ['email' => 'user@test.com', 'password' => 'password123', 'name' => 'Regular User', 'role' => 'User'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->supabase = app(SupabaseService::class);

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
     * Test full auth flow for a given account: signin, getUser, refresh, signout
     */
    protected function runAuthFlow(array $account)
    {
        // Sign in
        $signinResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $account['email'],
            'password' => $account['password'],
        ]);
        $signinResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token', 'user']);
        $data = $signinResponse->json();
        $this->assertEquals($account['email'], $data['user']['email']);
        $token = $data['access_token'];
        $refresh = $data['refresh_token'];

        // Get user details
        $userResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/bcms/auth/user');
        $userResponse->assertStatus(200)
            ->assertJson(['user' => ['email' => $account['email']]]);

        // Refresh token
        $refreshResponse = $this->postJson('/bcms/auth/refresh', ['refresh_token' => $refresh]);
        $refreshResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token', 'user']);

        // Sign out
        $signoutResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/bcms/auth/signout');
        $signoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        // After logout, token should be invalid
        $afterLogout = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/bcms/auth/user');
        $afterLogout->assertStatus(401);
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
     * Manager-only: list users
     */
    public function test_manager_can_list_users()
    {
        $token = $this->getAccessToken($this->manager);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/bcms/users');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    public function test_regular_user_cannot_list_users()
    {
        $token = $this->getAccessToken($this->user);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/bcms/users');
        $response->assertStatus(403);
    }    /**
     * Manager-only: create user
     */
    public function test_manager_can_create_user_endpoint()
    {
        $token = $this->getAccessToken($this->manager);
        $newEmail = 'new_' . time() . '@example.com';
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->postJson('/bcms/users', [
                             'email' => $newEmail,
                             'name' => 'Created User',
                             'role' => 'User',
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                         ]);
        $response->assertStatus(201)
                 ->assertJsonPath('user.email', $newEmail);
    }    public function test_regular_user_cannot_create_user_endpoint()
    {
        $token = $this->getAccessToken($this->user);
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->postJson('/bcms/users', [
                             'email' => 'fail_' . time() . '@example.com',
                             'name' => 'Should Fail',
                             'role' => 'User',
                             'password' => 'password123',
                             'password_confirmation' => 'password123',
                         ]);
        $response->assertStatus(403);
    }

    /**
     * Manager-only: show user
     */
    public function test_manager_can_show_user()
    {
        $token = $this->getAccessToken($this->manager);
        
        // First create a user to show
        $newEmail = 'show_test_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Show Test User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Now show the user
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/bcms/users/' . $userId);
        $response->assertStatus(200)
                 ->assertJsonPath('user.email', $newEmail)
                 ->assertJsonPath('user.name', 'Show Test User');
    }

    public function test_regular_user_cannot_show_user()
    {
        $token = $this->getAccessToken($this->user);
        $managerToken = $this->getAccessToken($this->manager);
        
        // Create a user with manager token first
        $newEmail = 'show_fail_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $managerToken])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Show Fail User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Try to show with regular user token
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/bcms/users/' . $userId);
        $response->assertStatus(403);
    }

    /**
     * Manager-only: update user
     */
    public function test_manager_can_update_user()
    {
        $token = $this->getAccessToken($this->manager);
        
        // First create a user to update
        $newEmail = 'update_test_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Update Test User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Now update the user (only name and role, not email to avoid Supabase complexity)
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->putJson('/bcms/users/' . $userId, [
                             'name' => 'Updated Test User',
                             'role' => 'Manager',
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonPath('user.name', 'Updated Test User')
                 ->assertJsonPath('user.role', 'Manager');
    }

    public function test_regular_user_cannot_update_user()
    {
        $token = $this->getAccessToken($this->user);
        $managerToken = $this->getAccessToken($this->manager);
        
        // Create a user with manager token first
        $newEmail = 'update_fail_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $managerToken])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Update Fail User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Try to update with regular user token
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->putJson('/bcms/users/' . $userId, [
                             'name' => 'Should Not Update',
                             'role' => 'Manager',
                         ]);
        $response->assertStatus(403);
    }

    /**
     * Manager-only: delete user
     */
    public function test_manager_can_delete_user()
    {
        $token = $this->getAccessToken($this->manager);
        
        // First create a user to delete
        $newEmail = 'delete_test_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Delete Test User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Now delete the user
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->deleteJson('/bcms/users/' . $userId);
        
        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully.']);

        // Verify user is deleted by trying to show it
        $showResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                             ->getJson('/bcms/users/' . $userId);
        $showResponse->assertStatus(404);
    }

    public function test_regular_user_cannot_delete_user()
    {
        $token = $this->getAccessToken($this->user);
        $managerToken = $this->getAccessToken($this->manager);
        
        // Create a user with manager token first
        $newEmail = 'delete_fail_' . time() . '@example.com';
        $createResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $managerToken])
                              ->postJson('/bcms/users', [
                                  'email' => $newEmail,
                                  'name' => 'Delete Fail User',
                                  'role' => 'User',
                                  'password' => 'password123',
                                  'password_confirmation' => 'password123',
                              ]);
        $createResponse->assertStatus(201);
        $userId = $createResponse->json('user.id');

        // Try to delete with regular user token
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->deleteJson('/bcms/users/' . $userId);
        $response->assertStatus(403);
    }
}
