<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Get the user's role from the user model
        $userRole = Auth::user()->role;
        
        // Check if the user has the required role
        if ($userRole !== $role) {
            return response()->json(['message' => 'Unauthorized. Requires ' . $role . ' role.'], 403);
        }

        return $next($request);
    }
}
