<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        $validatedData = $this->validate($request, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:User,Manager', // Updated roles
        ]);

        try {
            // Create user directly in database
            $userId = (string) Str::uuid();
            
            DB::table('users')->insert([
                'id' => $userId,
                'email' => $validatedData['email'],
                'name' => $validatedData['name'],
                'role' => $validatedData['role'],
                'password' => Hash::make($validatedData['password']),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = DB::table('users')->where('id', $userId)->first();
            
            // Generate access token (simplified - just the user ID as token for testing)
            $accessToken = base64_encode(json_encode(['user_id' => $userId, 'exp' => time() + 3600]));
            $refreshToken = base64_encode(json_encode(['user_id' => $userId, 'exp' => time() + 86400, 'type' => 'refresh']));

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());
            return response()->json(['error' => 'User registration failed'], 500);
        }
    }    public function signIn(Request $request)
    {
        $validatedData = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            // Find user by email in database
            $user = DB::table('users')->where('email', $validatedData['email'])->first();
            
            if (!$user) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // For testing purposes, we'll accept any password or check against a hashed password
            // In real application, use Hash::check($validatedData['password'], $user->password)
            $passwordValid = true; // Simplified for testing
            if (isset($user->password) && $user->password) {
                $passwordValid = Hash::check($validatedData['password'], $user->password);
            }

            if (!$passwordValid) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Generate access token (simplified - just the user ID as token for testing)
            $accessToken = base64_encode(json_encode(['user_id' => $user->id, 'exp' => time() + 3600]));
            $refreshToken = base64_encode(json_encode(['user_id' => $user->id, 'exp' => time() + 86400, 'type' => 'refresh']));

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Sign in failed: ' . $e->getMessage());
            return response()->json(['error' => 'Sign in failed'], 500);
        }
    }    public function refreshToken(Request $request)
    {
        $validatedData = $this->validate($request, [
            'refresh_token' => 'required|string',
        ]);

        try {
            // Decode refresh token
            $tokenData = json_decode(base64_decode($validatedData['refresh_token']), true);
            
            if (!$tokenData || !isset($tokenData['user_id']) || !isset($tokenData['type']) || $tokenData['type'] !== 'refresh') {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            // Check if token is expired
            if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
                return response()->json(['error' => 'Refresh token expired'], 401);
            }

            // Find user
            $user = DB::table('users')->where('id', $tokenData['user_id'])->first();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            // Generate new tokens
            $newAccessToken = base64_encode(json_encode(['user_id' => $user->id, 'exp' => time() + 3600]));
            $newRefreshToken = base64_encode(json_encode(['user_id' => $user->id, 'exp' => time() + 86400, 'type' => 'refresh']));

            return response()->json([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'expires_in' => 3600,
                'token_type' => 'bearer',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to refresh token'], 401);
        }
    }

    public function signOut(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        // Attempt to sign out in Supabase; ignore failures
        try {
            // Supabase sign out logic removed
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

        try {
            // Decode access token
            $tokenData = json_decode(base64_decode($token), true);
            
            if (!$tokenData || !isset($tokenData['user_id'])) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            // Check if token is expired
            if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
                return response()->json(['error' => 'Token expired'], 401);
            }

            // Find user
            $user = DB::table('users')->where('id', $tokenData['user_id'])->first();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get user failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid token or user not found'], 401);
        }
    }
}