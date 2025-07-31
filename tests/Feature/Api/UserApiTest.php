<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private $managerId;
    private $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test manager user for authentication
        $this->managerId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $this->managerId,
            'email' => 'manager@test.com',
            'name' => 'Test Manager',
            'role' => 'Manager',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a proper token for the manager
        $this->managerToken = base64_encode(json_encode([
            'user_id' => $this->managerId,
            'exp' => time() + 3600
        ]));
    }

    public function test_can_update_user_password_with_manager_role()
    {
        // Create a test user to update
        $userId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'user@test.com',
            'name' => 'Test User',
            'role' => 'User',
            'password' => Hash::make('oldpassword'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
            'Content-Type' => 'application/json',
        ])->putJson("/bcms/users/{$userId}", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'User updated successfully.']);

        // Verify the password was hashed in the database
        $updatedUser = DB::table('users')->where('id', $userId)->first();
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
        $this->assertFalse(Hash::check('oldpassword', $updatedUser->password));
    }

    public function test_can_create_user_with_hashed_password()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
            'Content-Type' => 'application/json',
        ])->postJson('/bcms/users', [
            'email' => 'newuser@test.com',
            'name' => 'New User',
            'role' => 'User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'User created successfully.']);

        // Verify the password was hashed in the database
        $user = DB::table('users')->where('email', 'newuser@test.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_password_update_requires_confirmation()
    {
        $userId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'user@test.com',
            'name' => 'Test User',
            'role' => 'User',
            'password' => Hash::make('oldpassword'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
            'Content-Type' => 'application/json',
        ])->putJson("/bcms/users/{$userId}", [
            'password' => 'newpassword123',
            // Missing password_confirmation
        ]);

        $response->assertStatus(422);
    }

    public function test_password_update_requires_minimum_length()
    {
        $userId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'user@test.com',
            'name' => 'Test User',
            'role' => 'User',
            'password' => Hash::make('oldpassword'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
            'Content-Type' => 'application/json',
        ])->putJson("/bcms/users/{$userId}", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_user_without_password()
    {
        $userId = \Illuminate\Support\Str::uuid()->toString();
        DB::table('users')->insert([
            'id' => $userId,
            'email' => 'user@test.com',
            'name' => 'Test User',
            'role' => 'User',
            'password' => Hash::make('oldpassword'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->managerToken,
            'Content-Type' => 'application/json',
        ])->putJson("/bcms/users/{$userId}", [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'User updated successfully.']);

        // Verify the password remains unchanged
        $updatedUser = DB::table('users')->where('id', $userId)->first();
        $this->assertTrue(Hash::check('oldpassword', $updatedUser->password));
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('updated@test.com', $updatedUser->email);
    }
} 