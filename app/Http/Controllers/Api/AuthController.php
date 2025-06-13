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
        // Temporarily bypass all logic including Supabase calls
        return response()->json(['message' => 'SignOut method reached cleanly (simplified).']);
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

        return response()->json([
            'user' => $user
        ]);
    }
}
