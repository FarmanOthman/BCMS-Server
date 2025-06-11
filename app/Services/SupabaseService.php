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
    }

    /**
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
            // Step 1: Create user in Supabase Auth using Admin API
            $response = Http::withHeaders([
                'apikey' => $this->supabaseAdminApiKey,
                'Authorization' => 'Bearer ' . $this->supabaseAdminApiKey, // Supabase Admin API often uses the API key as Bearer token
                'Content-Type' => 'application/json',
            ])->post($this->supabaseUrl . '/auth/v1/admin/users', [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true, // Auto-confirm email for simplicity, adjust as needed
                // You can add other user_metadata or app_metadata here if needed
            ]);

            if (!$response->successful()) {
                Log::error('Supabase user creation failed for email: ' . $email, [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $supabaseUser = $response->json();
            $userId = $supabaseUser['id'];

            // Step 2: Insert user details into the public Users table
            // Ensure your User model is set up to use the Supabase connection if it's different
            // For simplicity, using DB facade here. Adjust if you have a User model for the public.Users table.
            DB::table('Users')->insert([
                'id' => $userId, // Use the ID from Supabase Auth
                'name' => $name,
                'role' => $role,
                'created_at' => now(),
            ]);

            Log::info('Supabase user and public Users record created successfully for email: ' . $email, ['userId' => $userId]);

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
    }

    /**
     * Get the current authenticated user's details from Supabase.
     *
     * @param string $accessToken The user's Supabase access token.
     * @return array|null
     */
    public function getUserByAccessToken(string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->supabaseApiKey,
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get($this->supabaseUrl . '/auth/v1/user');

            if ($response->successful()) {
                $supabaseUser = $response->json();
                $userId = $supabaseUser['id'];

                // Fetch additional details from the public Users table
                $appUser = DB::table('Users')->where('id', $userId)->first();

                if ($appUser) {
                    // Combine auth user data with public Users table data
                    return array_merge($supabaseUser, (array)$appUser);
                }
                Log::warning('Supabase user found in auth.users but not in public.Users table', ['userId' => $userId]);
                return $supabaseUser; // Return auth user data if not found in public.Users
            }

            Log::error('Failed to get user by access token', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (Throwable $e) {
            Log::error('Error fetching user by access token', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
