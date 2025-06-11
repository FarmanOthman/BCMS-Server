<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Services\SupabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception; // Import base Exception class

class UserController extends Controller
{
    protected SupabaseService $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
        // Middleware are now applied in routes/api.php
        // $this->middleware('auth:sanctum')->only('createUser');
        // $this->middleware('can:create,App\Models\User')->only('createUser');
    }

    /**
     * Create a new user.
     *
     * @param CreateUserRequest $request
     * @return JsonResponse
     */
    public function createUser(CreateUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            $createdUser = $this->supabaseService->createUser(
                $validatedData['email'],
                $validatedData['password'],
                $validatedData['name'],
                $validatedData['role']
            );

            if ($createdUser) {
                // Exclude sensitive information before sending the response
                unset($createdUser['password']); // Ensure password is not in the Supabase response array if it ever is
                // You might want to further refine what's returned, e.g., only id, email, name, role, created_at
                $responseData = [
                    'id' => $createdUser['id'],
                    'email' => $createdUser['email'],
                    'name' => $validatedData['name'], // Name from request as it's stored in public.Users
                    'role' => $validatedData['role'], // Role from request
                    'created_at' => $createdUser['created_at'], // From Supabase auth user
                    'updated_at' => $createdUser['updated_at']  // From Supabase auth user
                ];

                Log::info('User creation endpoint success for email: ' . $validatedData['email']);
                return response()->json([
                    'message' => 'User created successfully.',
                    'user' => $responseData
                ], 201);
            }

            // This case might be redundant if createUser always throws an exception on Supabase failure
            Log::error('User creation endpoint failed for email (SupabaseService returned null): ' . $validatedData['email']);
            return response()->json(['message' => 'Failed to create user. Please check logs.'], 500);

        } catch (Exception $e) {
            // Catching a generic Exception. You might want to catch more specific exceptions 
            // if SupabaseService or HTTP client throws them (e.g., GuzzleHttp\Exception\ClientException)
            Log::error('User creation endpoint exception for email: ' . $validatedData['email'], [
                'error_message' => $e->getMessage(),
                // 'error_trace' => $e->getTraceAsString() // Be cautious with logging full traces
            ]);

            // Check if the error message indicates a duplicate email or similar user-facing issue
            // This is a basic check; Supabase might return specific error codes or messages
            if (str_contains(strtolower($e->getMessage()), 'duplicate key value violates unique constraint') || 
                str_contains(strtolower($e->getMessage()), 'user already registered')) {
                return response()->json(['message' => 'This email address is already in use.'], 422); // 422 Unprocessable Entity
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

        $supabaseUser = $this->supabaseService->getUserByAccessToken($token);

        if ($supabaseUser) {
            return response()->json($supabaseUser);
        }

        return response()->json(['message' => 'Could not retrieve user from Supabase.'], 404);
    }
}
