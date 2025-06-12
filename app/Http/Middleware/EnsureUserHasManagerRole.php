<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasManagerRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'Manager') { // Or use Auth::user()->hasRole('Manager') if you prefer
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized. Manager role required.'], 403);
    }
}
