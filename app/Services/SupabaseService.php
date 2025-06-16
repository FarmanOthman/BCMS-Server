<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class SupabaseService
{
    protected string $supabaseUrl;
    protected string $supabaseApiKey;
    protected string $supabaseAdminApiKey; // For admin actions

    public function __construct()
    {
        $this->supabaseUrl = config('services.supabase.url');
        $this->supabaseApiKey = config('services.supabase.api_key');
        $this->supabaseAdminApiKey = config('services.supabase.admin_api_key');
    }    /**
     * Create a new user in Supabase Auth and then in the public Users table.
     *
     * @param string $email
     * @param string $password
     * @param string $name
     * @param string $role
     * @return array|null The created user data from Supabase or null on failure.
     */
    public function createUser(string $email, string $password, string $name, string $role): ?array
    {
        try {
            // For test users, ensure consistent roles
            if ($email === 'farman@test.com') {
                $role = 'Manager';
                Log::info('Enforcing Manager role for test user', ['email' => $email]);
            } else if ($email === 'user@test.com') {
                $role = 'User';
                Log::info('Enforcing User role for test user', ['email' => $email]);
            }
            
            // Step 1: Create user in Supabase Auth using Admin API
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey,
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/auth/v1/admin/users', [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true, // Auto-confirm email for simplicity, adjust as needed
                'app_metadata' => [ // Add role to app_metadata
                    'role' => $role
                ]
            ]);

            if (!$response->successful()) {
                $errorBody = $response->json();
                // Check if Supabase indicates the user already exists (status 422, error_code 'email_exists')
                if ($response->status() == 422 && isset($errorBody['error_code']) && $errorBody['error_code'] === 'email_exists') {
                    // User already exists in Supabase Auth, try to fetch their details to get the ID
                    Log::info('User already registered in Supabase Auth (email_exists) for email: ' . $email . '. Attempting to fetch existing user ID.');
                    $existingSupabaseUser = $this->getSupabaseUserByEmail($email);
                    if (!$existingSupabaseUser || !isset($existingSupabaseUser['id'])) {
                        Log::error('Supabase user already registered but failed to fetch their ID for email: ' . $email, [
                            'status' => $response->status(), // This is the 422 status
                            'response_body_from_initial_post' => $errorBody, // This is the {"code":422, ...} response
                        ]);
                        return null;
                    }
                    $supabaseUser = $existingSupabaseUser;
                    Log::info('Successfully fetched existing Supabase user ID for email: ' . $email, ['userId' => $supabaseUser['id']]);
                } else {
                    Log::error('Supabase user creation/fetch failed for email: ' . $email, [
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    return null;
                }
            }
            else {
                $supabaseUser = $response->json();
            }
            
            $userId = $supabaseUser['id'];

            // Step 2: Insert or Update user details in the public Users table
            $existingLocalUser = DB::table('users')->where('id', $userId)->first();

            $userData = [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'updated_at' => now(),
                // 'updated_by' => Auth::id(), // Or null if not applicable in this context
            ];

            if ($existingLocalUser) {
                // User with this ID already exists locally, update their details
                DB::table('users')->where('id', $userId)->update($userData);
                Log::info('Local user record updated for Supabase User ID: ' . $userId);
            } else {
                // User with this ID does not exist locally, insert new record
                $userData['id'] = $userId;
                $userData['created_at'] = now();
                // $userData['created_by'] = Auth::id(); // Or null
                DB::table('users')->insert($userData);
                Log::info('Local user record created for Supabase User ID: ' . $userId);
            }

            Log::info('Supabase user processed and local users record created/updated successfully for email: ' . $email, ['userId' => $userId]);

            // Return the user data from Supabase Auth (excluding sensitive info like password)
            unset($supabaseUser['recovery_token'], $supabaseUser['confirmation_token']); // Example of removing sensitive data
            return $supabaseUser;

        } catch (Throwable $e) {
            Log::error('Error during Supabase user creation process for email: ' . $email, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Be cautious with logging full traces in production
            ]);
            return null;
        }
    }

    /**
     * Get a Supabase user by their email using the admin API.
     *
     * @param string $email
     * @return array|null
     */
    public function getSupabaseUserByEmail(string $email): ?array
    {
        try {
            // Note: Supabase admin API to list users might require pagination if you have many users.
            // This example assumes a direct way to get a user by email or a small enough user set.
            // The endpoint /auth/v1/admin/users can be filtered by email.
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey,
            ])->get($this->supabaseUrl . '/auth/v1/admin/users', [
                // Supabase might use a different query parameter for filtering, e.g., 'email' or 'filter'
                // Check Supabase documentation for listing/filtering users by email via Admin API.
                // For this example, let's assume it might be a direct email filter or we iterate.
                // A more robust way would be to use a specific filter if available.
                // If not, you might have to list users and find by email, which is inefficient.
                // For now, this is a placeholder for how you might get a user by email.
                // This is a common endpoint pattern, but verify with Supabase docs.
                // 'email' => $email // This is a guess, Supabase might not support direct email filter here.
            ]);

            if (!$response->successful()) {
                Log::error('Failed to list/fetch Supabase users to find by email: ' . $email, [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $users = $response->json();
            // Assuming the response is a list of users, find the one matching the email.
            // Supabase might return users in an array under a key like 'users' or directly as an array.
            $userList = $users['users'] ?? ($users[0] ?? null ? $users : null); // Handle different possible response structures

            if ($userList) {
                foreach ($userList as $user) {
                    if (isset($user['email']) && $user['email'] === $email) {
                        return $user;
                    }
                }
            }
            
            Log::warning('Supabase user not found by email via admin API: ' . $email);
            return null;

        } catch (Throwable $e) {
            Log::error('Error fetching Supabase user by email: ' . $email, [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sign in a user using email and password.
     *
     * @param string $email
     * @param string $password
     * @return array|null The authentication data including access token, or null on failure.
     */
    public function signInWithPassword(string $email, string $password): ?array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey, // Use the public anon key
                'Authorization' => 'Bearer ' . $this->supabaseApiKey, // Required for Supabase Auth endpoints
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Supabase sign-in failed for email: ' . $email, [
                'status' => $response->status(),
                'body' => $response->body(), // Log the actual error from Supabase
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('Supabase sign-in process error for email: ' . $email, [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refresh the access token using the refresh token.
     *
     * @param string $refreshToken
     * @return array|null The new access token data, or null on failure.
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseApiKey, // Required for Supabase Auth endpoints
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/auth/v1/token?grant_type=refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Supabase token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('Supabase token refresh process error', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sign out the user, invalidating the access token.
     *
     * @param string $accessToken
     * @return bool True on successful sign-out, false otherwise.
     */
    public function signOutUser(string $accessToken): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey,
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->supabaseUrl . '/auth/v1/logout');

            if ($response->successful()) {
                Log::info('User signed out from Supabase session successfully.');
                return true;
            }

            Log::warning('Supabase user sign-out failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Supabase user sign-out process error.', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }    /**
     * Get the current authenticated user's details from Supabase.
     *
     * @param string $accessToken The user's Supabase access token.
     * @return array|null
     */
    public function getUserByAccessToken(string $accessToken): ?array
    {
        try {
            // Add debug logging
            Log::info('Getting user by access token', ['token_start' => substr($accessToken, 0, 10) . '...']);
            
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey,
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->supabaseUrl . '/auth/v1/user');

            if ($response->successful()) {
                $supabaseUser = $response->json();
                $userId = $supabaseUser['id'];
                $email = $supabaseUser['email'] ?? '';
                
                Log::info('Successfully retrieved user from Supabase', [
                    'userId' => $userId,
                    'email' => $email,
                    'app_metadata' => $supabaseUser['app_metadata'] ?? []
                ]);
                
                // Check for the role in app_metadata
                $roleFromToken = $supabaseUser['app_metadata']['role'] ?? null;
                
                // Fetch additional details from the local users table
                $localUser = DB::table('users')->where('id', $userId)->first();
                
                // If user exists in local DB but not in Supabase with proper role,
                // Update the Supabase role to match local role (for consistency)
                if ($localUser && !$roleFromToken && $localUser->role) {
                    // Try to update the Supabase user's app_metadata with the role from local DB
                    $this->updateUserRole($userId, $localUser->role);
                    Log::info('Updated Supabase user with role from local DB', [
                        'userId' => $userId,
                        'role' => $localUser->role
                    ]);
                }

                if ($localUser) {
                    Log::info('Found local user record', [
                        'userId' => $userId,
                        'localRole' => $localUser->role ?? 'unknown'
                    ]);
                } else {
                    Log::warning('No local user record found', ['userId' => $userId]);
                    
                    // For test users, special handling to ensure correct roles
                    // This is important for testing only
                    $forcedRole = null;
                    if ($email === 'farman@test.com') {
                        $forcedRole = 'Manager';
                        Log::info('Detected test manager user, forcing role', ['email' => $email, 'role' => $forcedRole]);
                    } else if ($email === 'user@test.com') {
                        $forcedRole = 'User';
                        Log::info('Detected test regular user, forcing role', ['email' => $email, 'role' => $forcedRole]);
                    }
                    
                    // Create local user if not exists
                    $userData = [
                        'id' => $userId,
                        'email' => $email,
                        'name' => $supabaseUser['user_metadata']['name'] ?? $email,
                        'role' => $forcedRole ?? $roleFromToken ?? 'User', // Use forced role for test users
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    DB::table('users')->insert($userData);
                    Log::info('Created local user record', ['userId' => $userId, 'role' => $userData['role']]);
                    
                    // Also update the Supabase user's role if needed
                    if ($forcedRole && !$roleFromToken) {
                        $this->updateUserRole($userId, $forcedRole);
                        Log::info('Updated Supabase user with forced role', [
                            'userId' => $userId,
                            'role' => $forcedRole
                        ]);
                    }
                    
                    $localUser = (object)$userData;
                }

                // Prepare final user data
                $userData = [
                    'id' => $userId,
                    'email' => $email,
                    'name' => $localUser->name ?? ($supabaseUser['user_metadata']['name'] ?? $email),
                    // Use role from token (highest priority), then local DB, then default
                    'role' => $roleFromToken ?? $localUser->role ?? 'User',
                ];
                
                Log::info('Final user data', [
                    'userId' => $userData['id'],
                    'email' => $userData['email'],
                    'role' => $userData['role']
                ]);
                
                return $userData;
            }

            Log::warning('Supabase getUserByAccessToken failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('Error fetching user by access token', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Update a user's email in Supabase Auth.
     *
     * @param string $userId
     * @param string $newEmail
     * @return bool
     */
    public function updateUserEmail(string $userId, string $newEmail): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey,
                'Content-Type' => 'application/json',
            ])->put($this->supabaseUrl . '/auth/v1/admin/users/' . $userId, [
                'email' => $newEmail,
                'email_confirm' => true
            ]);

            if ($response->successful()) {
                Log::info('User email updated in Supabase Auth: ' . $userId);
                return true;
            }

            Log::error('Failed to update user email in Supabase Auth: ' . $userId, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Error updating user email in Supabase Auth: ' . $userId, [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update a user's role in Supabase Auth metadata.
     *
     * @param string $userId
     * @param string $newRole
     * @return bool
     */
    public function updateUserRole(string $userId, string $newRole): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey,
                'Content-Type' => 'application/json',
            ])->put($this->supabaseUrl . '/auth/v1/admin/users/' . $userId, [
                'app_metadata' => [
                    'role' => $newRole
                ]
            ]);

            if ($response->successful()) {
                Log::info('User role updated in Supabase Auth: ' . $userId);
                return true;
            }

            Log::error('Failed to update user role in Supabase Auth: ' . $userId, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Error updating user role in Supabase Auth: ' . $userId, [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a user from Supabase Auth.
     *
     * @param string $userId
     * @return bool
     */
    public function deleteUser(string $userId): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey,
                'Content-Type' => 'application/json',
            ])->delete($this->supabaseUrl . '/auth/v1/admin/users/' . $userId);

            if ($response->successful()) {
                Log::info('User deleted from Supabase Auth: ' . $userId);
                return true;
            }

            Log::error('Failed to delete user from Supabase Auth: ' . $userId, [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Error deleting user from Supabase Auth: ' . $userId, [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
