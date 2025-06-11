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
        // Also check if the role is not already set (e.g., by a previous step or if already loaded)
        if ($laravelUser instanceof User && !$laravelUser->role) {
            // Fetch additional user data from the 'Users' table in Supabase public schema
            // $laravelUser->id should be the Supabase UUID from the JWT's sub claim
            $supabasePublicUserData = DB::table('Users')->where('id', $laravelUser->id)->first();

            if ($supabasePublicUserData) {
                // Populate the Laravel User model instance with data from the 'Users' table
                // Ensure properties exist on your App\Models\User model or use fillable/attributes
                $laravelUser->name = $supabasePublicUserData->name; // Assuming name is also in public.Users
                $laravelUser->role = $supabasePublicUserData->role;
                // You could also set other attributes here if needed
                // $laravelUser->setRawAttributes((array)$supabasePublicUserData, true); // Alternative way to set multiple attributes
            }
        }

        return $next($request);
    }
}
