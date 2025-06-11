<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a Gate for creating users
        // This checks if the authenticated user has the 'Manager' role
        Gate::define('create', function (User $user, string $model) {
            if ($model === User::class) {
                 // Assuming your User model (which represents the authenticated user)
                 // has a 'role' attribute fetched from your 'Users' table in Supabase public schema.
                 // This requires that when a user authenticates, their role is loaded.
                return $user->hasRole('Manager');
            }
            return false;
        });

        // If you prefer to use Policies, you can generate one:
        // php artisan make:policy UserPolicy --model=User
        // And then register it in AuthServiceProvider.php
        // For this example, a simple Gate is sufficient.
    }
}
