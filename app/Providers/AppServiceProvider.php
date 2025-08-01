<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Sale;
use App\Models\FinanceRecord;
use App\Observers\SaleObserver;
use App\Observers\FinanceRecordObserver;
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
        // Register the Sale observer for automatic report generation
        Sale::observe(SaleObserver::class);
        
        // Register the FinanceRecord observer for automatic report regeneration
        FinanceRecord::observe(FinanceRecordObserver::class);

        // Define a Gate for creating users
        // This checks if the authenticated user has the 'Manager' role
        Gate::define('create', function (User $user, string $model) {
            if ($model === User::class) {
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
