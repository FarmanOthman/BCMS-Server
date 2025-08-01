<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Authentication API Integration Test
 * 
 * This test works with the local PostgreSQL authentication system.
 * Tests user registration, sign-in, token management, and user information retrieval.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $testEmail = 'test@example.com';
    protected string $testPassword = 'password123';
    protected string $managerEmail = 'manager@example.com';
    protected string $managerPassword = 'password123';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users in the database
        $this->createTestUsers();
    }

    protected function createTestUsers()
    {
        // Create regular user
        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'email' => $this->testEmail,
            'name' => 'Test User',
            'role' => 'User',
            'password' => Hash::make($this->testPassword),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create manager user
        DB::table('users')->insert([
            'id' => (string) Str::uuid(),
            'email' => $this->managerEmail,
            'name' => 'Manager User',
            'role' => 'Manager',
            'password' => Hash::make($this->managerPassword),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @test
     * Test successful sign in with valid credentials
     */
    public function it_can_sign_in_with_valid_credentials()
    {
        $response = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'refresh_token',
                    'user',
                    'expires_in',
                    'token_type'
                ])
                ->assertJson([
                    'token_type' => 'bearer'
                ]);

        // Verify user data structure
        $userData = $response->json('user');
        $this->assertNotNull($userData);
        $this->assertEquals($this->testEmail, $userData['email']);
        $this->assertNotEmpty($response->json('access_token'));
        $this->assertNotEmpty($response->json('refresh_token'));
    }

    /**
     * @test
     * Test sign in failure with invalid credentials
     */
    public function it_fails_sign_in_with_invalid_credentials()
    {
        $response = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Invalid credentials'
                ]);
    }

    /**
     * @test
     * Test validation for required fields
     */
    public function it_requires_email_and_password_for_sign_in()
    {
        $response = $this->postJson('/bcms/auth/signin', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * @test
     * Test email format validation
     */
    public function it_validates_email_format_for_sign_in()
    {
        $response = $this->postJson('/bcms/auth/signin', [
            'email' => 'invalid-email',
            'password' => $this->testPassword
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     * Test token refresh with valid refresh token
     */
    public function it_can_refresh_access_token_with_valid_refresh_token()
    {
        // First, sign in to get a refresh token
        $signInResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $signInResponse->assertStatus(200);
        $refreshToken = $signInResponse->json('refresh_token');
        $this->assertNotNull($refreshToken, 'Refresh token should be present in sign in response');

        // Now test refreshing the token
        $response = $this->postJson('/bcms/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'refresh_token',
                    'user',
                    'expires_in',
                    'token_type'
                ]);
        
        $this->assertNotEmpty($response->json('access_token'));
    }

    /**
     * @test
     * Test refresh failure with invalid token
     */
    public function it_fails_refresh_with_invalid_refresh_token()
    {
        $response = $this->postJson('/bcms/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Invalid refresh token'
                ]);
    }

    /**
     * @test
     * Test validation for refresh token requirement
     */
    public function it_requires_refresh_token_for_refresh_endpoint()
    {
        $response = $this->postJson('/bcms/auth/refresh', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['refresh_token']);
    }

    /**
     * @test
     * Test successful sign out with valid token
     */
    public function it_can_sign_out_with_valid_token()
    {
        // Sign in to get a token
        $signInResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $signInResponse->assertStatus(200);
        $accessToken = $signInResponse->json('access_token');
        $this->assertNotNull($accessToken);

        // Test signing out
        $response = $this->postJson('/bcms/auth/signout', [], [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Successfully logged out'
                ]);
    }

    /**
     * @test
     * Test sign out failure without token
     */
    public function it_fails_sign_out_without_token()
    {
        $response = $this->postJson('/bcms/auth/signout');

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'No token provided'
                ]);
    }

    /**
     * @test
     * Test sign out with invalid token (should still return success per controller logic)
     */
    public function it_handles_sign_out_with_invalid_token_gracefully()
    {
        $response = $this->postJson('/bcms/auth/signout', [], [
            'Authorization' => 'Bearer invalid-token'
        ]);

        // According to the controller, sign out always returns 200 even with invalid token
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Successfully logged out'
                ]);
    }

    /**
     * @test
     * Test getting user info with valid token
     */
    public function it_can_get_user_info_with_valid_token()
    {
        // Sign in to get a token
        $signInResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $signInResponse->assertStatus(200);
        $accessToken = $signInResponse->json('access_token');
        $this->assertNotNull($accessToken);

        // Test getting user info
        $response = $this->getJson('/bcms/auth/user', [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'email'
                    ]
                ]);

        // Verify email matches
        $userData = $response->json('user');
        $this->assertEquals($this->testEmail, $userData['email']);
    }

    /**
     * @test
     * Test getting user info without token
     */
    public function it_fails_get_user_without_token()
    {
        $response = $this->getJson('/bcms/auth/user');

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'No token provided'
                ]);
    }

    /**
     * @test
     * Test getting user info with invalid token
     */
    public function it_fails_get_user_with_invalid_token()
    {
        $response = $this->getJson('/bcms/auth/user', [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Invalid token'
                ]);
    }

    /**
     * @test
     * Test rate limiting on sign in endpoint
     */
    public function it_respects_rate_limiting_on_sign_in()
    {
        // Make 6 requests with wrong password (limit is 5 per minute according to routes)
        $responses = [];
        for ($i = 0; $i < 6; $i++) {
            $responses[] = $this->postJson('/bcms/auth/signin', [
                'email' => $this->testEmail,
                'password' => 'wrongpassword'
            ]);
        }

        // First 5 should be 401 (unauthorized)
        for ($i = 0; $i < 5; $i++) {
            $responses[$i]->assertStatus(401);
        }

        // 6th should be rate limited (429)
        $responses[5]->assertStatus(429);
    }

    /**
     * @test
     * Test multiple valid sign in attempts should all succeed
     */
    public function it_handles_multiple_valid_sign_in_attempts()
    {
        // Multiple valid sign in attempts should all succeed (within rate limit)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/bcms/auth/signin', [
                'email' => $this->testEmail,
                'password' => $this->testPassword
            ]);

            $response->assertStatus(200)
                    ->assertJsonStructure(['access_token', 'refresh_token', 'user']);
            
            $this->assertNotEmpty($response->json('access_token'));
        }
    }

    /**
     * @test
     * Test data consistency between sign in and get user
     */
    public function it_returns_consistent_user_data_structure()
    {
        // Sign in
        $signInResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $signInResponse->assertStatus(200);
        $accessToken = $signInResponse->json('access_token');
        $signInUser = $signInResponse->json('user');

        // Get user info
        $userResponse = $this->getJson('/bcms/auth/user', [
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $userResponse->assertStatus(200);
        $getUserUser = $userResponse->json('user');

        // Both responses should have consistent user data
        $this->assertEquals($signInUser['email'], $getUserUser['email']);
        $this->assertEquals($signInUser['id'], $getUserUser['id']);
    }

    /**
     * @test
     * Test sign in with manager account
     */
    public function it_can_sign_in_manager_account()
    {
        $response = $this->postJson('/bcms/auth/signin', [
            'email' => $this->managerEmail,
            'password' => $this->managerPassword
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'access_token',
                    'refresh_token',
                    'user',
                    'expires_in',
                    'token_type'
                ]);

        // Verify it's the manager account
        $userData = $response->json('user');
        $this->assertEquals($this->managerEmail, $userData['email']);
        $this->assertEquals('Manager', $userData['role']);
    }

    /**
     * @test
     * Test complete authentication flow
     */
    public function it_completes_full_authentication_flow()
    {
        // 1. Sign in
        $signInResponse = $this->postJson('/bcms/auth/signin', [
            'email' => $this->testEmail,
            'password' => $this->testPassword
        ]);

        $signInResponse->assertStatus(200);
        $accessToken = $signInResponse->json('access_token');
        $refreshToken = $signInResponse->json('refresh_token');

        // 2. Get user info
        $userResponse = $this->getJson('/bcms/auth/user', [
            'Authorization' => 'Bearer ' . $accessToken
        ]);
        $userResponse->assertStatus(200);

        // 3. Refresh token
        $refreshResponse = $this->postJson('/bcms/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);
        $refreshResponse->assertStatus(200);
        $newAccessToken = $refreshResponse->json('access_token');

        // 4. Use new token to get user info
        $newUserResponse = $this->getJson('/bcms/auth/user', [
            'Authorization' => 'Bearer ' . $newAccessToken
        ]);
        $newUserResponse->assertStatus(200);

        // 5. Sign out
        $signOutResponse = $this->postJson('/bcms/auth/signout', [], [
            'Authorization' => 'Bearer ' . $newAccessToken
        ]);
        $signOutResponse->assertStatus(200);
    }


}
