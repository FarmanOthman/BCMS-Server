<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Import the User model
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $laravelUser = Auth::user();

        // Check if the user is authenticated and is an instance of our App\Models\User
        if ($laravelUser instanceof User && !$laravelUser->role) {
            $userData = DB::table('users')->where('id', $laravelUser->id)->first();
            if ($userData) {
                $laravelUser->name = $userData->name;
                $laravelUser->role = $userData->role;
            }
        }

        return $next($request);
    }
}
