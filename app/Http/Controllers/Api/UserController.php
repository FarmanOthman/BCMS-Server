<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception; // Import base Exception class

class UserController extends Controller
{
    /**
     * Create a new user.
     *
     * @param CreateUserRequest $request
     * @return JsonResponse
     */
    public function createUser(CreateUserRequest $request): JsonResponse
    {
        // No need for ensureManager, the route middleware handles this
        $validatedData = $request->validated();

        try {
            Log::info('Creating user directly in local DB: ' . $validatedData['email']);
            
            $userId = \Illuminate\Support\Str::uuid()->toString();
            
            // Insert the user into the local database
            DB::table('users')->insert([
                'id' => $userId,
                'email' => $validatedData['email'],
                'name' => $validatedData['name'],
                'role' => $validatedData['role'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $responseData = [
                'id' => $userId,
                'email' => $validatedData['email'],
                'name' => $validatedData['name'],
                'role' => $validatedData['role'],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];
            
            Log::info('User created successfully in local DB: ' . $validatedData['email']);
            
            return response()->json([
                'message' => 'User created successfully.',
                'user' => $responseData
            ], 201);

        } catch (Exception $e) {
            Log::error('User creation endpoint exception for email: ' . $validatedData['email'], [
                'error_message' => $e->getMessage(),
            ]);

            // Check if the error message indicates a duplicate email
            if (str_contains(strtolower($e->getMessage()), 'duplicate key value violates unique constraint') || 
                str_contains(strtolower($e->getMessage()), 'unique constraint')) {
                return response()->json(['message' => 'This email address is already in use.'], 422);
            }
            
            return response()->json(['message' => 'An unexpected error occurred while creating the user.'], 500);
        }
    }

    /**
     * Get the authenticated user's details.
     *
     * This is an example of how you might fetch the current user's (e.g., a Manager)
     * details if they are logged in via Supabase through your Laravel backend.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        // This assumes the user is authenticated via Sanctum and the token is a Supabase JWT
        // Or, you have a way to get the Supabase access token for the current Laravel user
        $user = Auth::user(); // This would be your Laravel User model instance

        // If you store the Supabase access token with your Laravel user model, you can use it:
        // $supabaseAccessToken = $user->supabase_access_token; // Fictional field
        // For this example, let's assume the Sanctum token IS the Supabase access token
        // This requires specific setup for Sanctum to accept Supabase JWTs

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Removed: $supabaseUser = $this->supabaseService->getUserByAccessToken($token);

        // In a real application, you would decode the Sanctum token to get the user ID
        // and then fetch the user from your local database.
        // For this example, we'll just return a placeholder response.
        return response()->json(['message' => 'Authenticated user details (placeholder).', 'user' => $user]);
    }

    /**     * Display a listing of all users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Get all users from the database
            $users = DB::table('users')->get();
            
            return response()->json(['data' => $users]);
        } catch (Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve users.'], 500);
        }
    }

    /**
     * Display the specified user.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Get user from database by ID
            $user = DB::table('users')->where('id', $id)->first();
            
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }
            
            return response()->json(['user' => $user]);
        } catch (Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve user.'], 500);
        }
    }

    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'role' => 'sometimes|string|in:Manager,User',
            ]);

            // Get the user
            $user = DB::table('users')->where('id', $id)->first();
            
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }
            
            Log::info('Updating user directly in local DB: ' . $id);
            
            // Update the user in the local database
            $updated = DB::table('users')
                ->where('id', $id)
                ->update([
                    'name' => $validatedData['name'] ?? $user->name,
                    'email' => $validatedData['email'] ?? $user->email,
                    'role' => $validatedData['role'] ?? $user->role,
                    'updated_at' => now(),
                ]);
                
            if ($updated) {
                $updatedUser = DB::table('users')->where('id', $id)->first();
                return response()->json([
                    'message' => 'User updated successfully.',
                    'user' => $updatedUser
                ]);
            }
            
            return response()->json(['message' => 'Failed to update user.'], 500);
        } catch (Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Check if user exists
            $user = DB::table('users')->where('id', $id)->first();
            
            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }
            
            Log::info('Deleting user directly from local DB: ' . $id);
            
            // Delete user from local database
            $deleted = DB::table('users')->where('id', $id)->delete();
            
            if ($deleted) {
                return response()->json(['message' => 'User deleted successfully.']);
            }
            
            return response()->json(['message' => 'Failed to delete user.'], 500);
        } catch (Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }
}
