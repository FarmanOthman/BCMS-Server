<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Sale;
use App\Models\FinanceRecord;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Observers\SaleObserver;
use App\Observers\FinanceRecordObserver;
use App\Observers\DailySalesReportObserver;
use App\Observers\MonthlySalesReportObserver;
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
        
        // Register the DailySalesReport observer for automatic monthly report updates
        DailySalesReport::observe(DailySalesReportObserver::class);
        
        // Register the MonthlySalesReport observer for automatic yearly report updates
        MonthlySalesReport::observe(MonthlySalesReportObserver::class);

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
