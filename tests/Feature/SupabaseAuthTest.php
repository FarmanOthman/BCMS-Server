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
    protected array $user = ['email' => 'user@test.com',  'password' => 'password123', 'name' => 'Regular User','role' => 'User'];    protected function setUp(): void
    {
        parent::setUp();
        $this->supabase = app(SupabaseService::class);

        // Ensure test users exist in Supabase and local DB
        foreach ([$this->manager, $this->user] as $account) {
            // Create or update the user in Supabase with the specified role
            $supabaseUser = $this->supabase->createUser(
                $account['email'],
                $account['password'],
                $account['name'],
                $account['role']
            );
            
            // Extra check: if user exists in Supabase but doesn't have the correct role,
            // update their role explicitly
            if ($supabaseUser && isset($supabaseUser['id'])) {
                // Ensure the role is properly set in Supabase
                $this->supabase->updateUserRole($supabaseUser['id'], $account['role']);
                
                // Ensure the role is properly set in the local database
                \Illuminate\Support\Facades\DB::table('users')
                    ->updateOrInsert(
                        ['id' => $supabaseUser['id']], 
                        [
                            'email' => $account['email'],
                            'name' => $account['name'],
                            'role' => $account['role'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
            }
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

    public function test_manager_can_authenticate_and_manage_tokens()
    {
        $this->runAuthFlow($this->manager);
    }

    public function test_regular_user_can_authenticate_and_manage_tokens()
    {
        $this->runAuthFlow($this->user);
    }
}
