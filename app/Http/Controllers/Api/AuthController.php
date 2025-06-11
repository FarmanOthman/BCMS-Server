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
        // Supabase admin API is needed to invalidate all refresh tokens for a user or a specific session.
        // The /auth/v1/logout endpoint only clears the cookie for Supabase-managed sessions.
        // For API-based auth, true logout means the client discards the JWT.
        // If you want to invalidate the access token on the server-side before it expires, 
        // you'd need a more complex setup (e.g., token blocklist).

        // This example will use the Supabase admin endpoint to sign out the user from all sessions.
        // This requires the user's Supabase ID, which you might not have directly here unless the token is passed.
        // A simpler client-side logout is just discarding the token.
        // For a more robust server-side initiated logout (e.g. admin revokes user session), you'd call Supabase admin API.

        // Let's assume for now, the client is responsible for discarding the token.
        // If you want to call Supabase's global signout for a user (requires user's access token):
        $userAccessToken = $request->bearerToken();
        if ($userAccessToken) {
            $success = $this->supabase->signOutUser($userAccessToken);
            if ($success) {
                return response()->json(['message' => 'User signed out from Supabase session. Client should discard token.']);
            }
            return response()->json(['message' => 'Failed to sign out from Supabase session, or token already invalid. Client should discard token.'], 400);
        }
        
        // If no token provided, it's essentially a client-side action.
        return response()->json(['message' => 'Client should discard the token.']);
    }
}
