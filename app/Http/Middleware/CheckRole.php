<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SupabaseService;

class CheckRole
{
    protected $supabaseService;
    
    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            Log::warning('No bearer token provided');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Use the SupabaseService to get user data from the access token
        $userData = $this->supabaseService->getUserByAccessToken($token);
        
        if (!$userData) {
            Log::warning('Invalid token or user not found');
            return response()->json(['message' => 'Invalid token or user not found.'], 401);
        }
        
        Log::info('User data retrieved', ['email' => $userData['email'] ?? 'unknown', 'role' => $userData['role'] ?? 'unknown']);
        
        // Check if the user has the required role
        if (!isset($userData['role']) || $userData['role'] !== $role) {
            Log::warning('User does not have required role', [
                'required' => $role,
                'actual' => $userData['role'] ?? 'none'
            ]);
            return response()->json(['message' => 'Unauthorized. Requires ' . $role . ' role.'], 403);
        }
        
        // User has required role, proceed
        return $next($request);
    }
}
