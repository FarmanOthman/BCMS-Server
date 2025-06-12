<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('supabase', function (Request $request) {
            $token = $request->bearerToken();

            if (!$token) {
                return null;
            }

            $supabaseService = app(SupabaseService::class);
            $userData = $supabaseService->getUserByAccessToken($token);

            if ($userData) {
                // Create a new User model instance or find an existing one
                // Ensure the 'role' attribute is set from $userData['role'] which prioritizes the token's app_metadata
                $user = User::firstOrNew(['id' => $userData['id']]);
                $user->fill($userData); // Fill other attributes like name, email
                $user->role = $userData['role']; // Explicitly set the role from the token/SupabaseService logic
                $user->exists = true; // Important if using firstOrNew and the user exists
                return $user;
            }

            return null;
        });
    }
}
