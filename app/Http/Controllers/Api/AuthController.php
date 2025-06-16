<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function signUp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|email|unique:users,email', // Assuming you have a users table for basic checks
                'password' => 'required|string|min:8',
                'name' => 'required|string|max:255',
                'role' => 'required|string|in:user,admin,editor', // Example roles
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = $this->supabase->createUser(
            $validatedData['email'],
            $validatedData['password'],
            $validatedData['name'],
            $validatedData['role']
        );

        if (!$user || isset($user['error'])) {
            Log::error('Supabase signup failed: ' . json_encode($user['error'] ?? ['message' => 'Unknown error']));
            return response()->json(['error' => 'User registration failed', 'details' => $user['error'] ?? 'Unknown Supabase error'], 500);
        }
        
        // Optionally, sign in the user immediately after registration
        $signInResponse = $this->supabase->signInWithPassword(
            $validatedData['email'],
            $validatedData['password']
        );

        if (!$signInResponse || !isset($signInResponse['access_token'])) {
            // User created but sign-in failed, might indicate an issue or just return created user
            return response()->json(['user' => $user, 'message' => 'User created, but auto sign-in failed.'], 201);
        }

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user, // This is the user object from Supabase createUser
            'access_token' => $signInResponse['access_token'],
            'refresh_token' => $signInResponse['refresh_token'] ?? null,
            'expires_in' => $signInResponse['expires_in'] ?? null,
            'token_type' => $signInResponse['token_type'] ?? 'bearer',
        ], 201);
    }

    public function signIn(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $response = $this->supabase->signInWithPassword(
            $request->input('email'),
            $request->input('password')
        );

        if (!$response || !isset($response['access_token'])) {
            return response()->json(['error' => 'Invalid credentials or failed to retrieve token.'], 401);
        }

        return response()->json([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null, // Ensure refresh_token is handled if not present
            'user' => $response['user'] ?? null,
            'expires_in' => $response['expires_in'] ?? null,
            'token_type' => $response['token_type'] ?? 'bearer',
        ]);
    }

    public function refreshToken(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $response = $this->supabase->refreshAccessToken($request->input('refresh_token'));

        if (!$response || !isset($response['access_token'])) {
            return response()->json(['error' => 'Failed to refresh token or invalid refresh token.'], 401);
        }

        return response()->json([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? null, // Supabase might return a new refresh token
            'user' => $response['user'] ?? null,
            'expires_in' => $response['expires_in'] ?? null,
            'token_type' => $response['token_type'] ?? 'bearer',
        ]);
    }

    public function signOut(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        // Attempt to sign out in Supabase; ignore failures
        try {
            $this->supabase->signOutUser($token);
        } catch (\Throwable $e) {
            // Log or ignore
        }

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getUser(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        $user = $this->supabase->getUserByAccessToken($token);
        
        if (!$user) {
            return response()->json(['error' => 'Invalid token or user not found'], 401);
        }

        return response()->json(['user' => $user]);
    }
}