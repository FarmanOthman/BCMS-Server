<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param string ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            Log::warning('No bearer token provided');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        try {
            // Decode access token
            $tokenData = json_decode(base64_decode($token), true);
            
            if (!$tokenData || !isset($tokenData['user_id'])) {
                Log::warning('Invalid token format');
                return response()->json(['message' => 'Invalid token.'], 401);
            }

            // Check if token is expired
            if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
                Log::warning('Token expired');
                return response()->json(['message' => 'Token expired.'], 401);
            }

            // Get user from database
            $user = DB::table('users')->where('id', $tokenData['user_id'])->first();
            
            if (!$user) {
                Log::warning('User not found for token');
                return response()->json(['message' => 'User not found.'], 401);
            }

            Log::info('User data retrieved', ['email' => $user->email, 'role' => $user->role]);
            
            // Check if the user has the required role
            if (!$user->role || !in_array($user->role, $roles)) {
                Log::warning('User does not have required role', [
                    'required' => implode(', ', $roles),
                    'actual' => $user->role ?? 'none'
                ]);
                return response()->json(['message' => 'Unauthorized. Requires one of the following roles: ' . implode(', ', $roles) . '.'], 403);
            }
            
            // User has required role, proceed
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Token validation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid token.'], 401);
        }
    }
}
