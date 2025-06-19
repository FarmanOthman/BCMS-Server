<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController; // Added import
use App\Http\Controllers\Api\MakeController; // Corrected use statement
use App\Http\Controllers\Api\ModelController; // Corrected use statement
use App\Http\Controllers\Api\BuyerController; // Added BuyerController import
use App\Http\Controllers\Api\SaleController; // Added SaleController import
use App\Http\Controllers\Api\DailySalesReportController; // Added DailySalesReportController import
use App\Http\Controllers\Api\MonthlySalesReportController; // Added MonthlySalesReportController import
use App\Http\Controllers\Api\YearlySalesReportController; // Added YearlySalesReportController import
use App\Http\Controllers\Api\FinanceRecordController; // Added import for FinanceRecordController

Route::prefix('bcms')->group(function () {
    // Routes requiring authentication and manager role for user management
    Route::middleware(['role:Manager'])->group(function () {
        // Complete CRUD for users (only accessible by managers)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'createUser']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
    
    // Public authentication routes
    Route::post('/auth/signin', [AuthController::class, 'signIn'])
        ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
    
    // Sign out for any authenticated user (middleware removed to let controller handle auth)
    Route::post('/auth/signout', [AuthController::class, 'signOut']);

    // Get current user info (requires authentication) - middleware removed
    Route::get('/auth/user', [AuthController::class, 'getUser']);
    
    // Public endpoints for listing and viewing cars
    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/{car}', [CarController::class, 'show']);

    // Other Car operations - Accessible to Manager and User roles
    Route::apiResource('/cars', CarController::class)
        ->except(['index', 'show'])
        ->middleware(['role:Manager,User']);

    // API resources for Makes and Models, accessible to Manager and User roles
    Route::apiResource('/makes', MakeController::class)->middleware(['role:Manager,User']);
    Route::apiResource('/models', ModelController::class)->middleware(['role:Manager,User']);

    // API resource for Buyers, accessible to Manager and User roles
    Route::apiResource('/buyers', BuyerController::class)->middleware(['role:Manager,User']);

    // API resource for Sales, restricted to Managers
    Route::apiResource('/sales', SaleController::class)->middleware(['role:Manager']);

    // Report Routes - Restricted to Managers (or other appropriate roles)
    Route::get('/reports/daily', [DailySalesReportController::class, 'show'])->middleware(['role:Manager']);
    Route::get('/reports/monthly', [MonthlySalesReportController::class, 'show'])->middleware(['role:Manager']);
    Route::get('/reports/yearly', [YearlySalesReportController::class, 'show'])->middleware(['role:Manager']);

    // API resource for Finance Records, restricted to Managers
    Route::apiResource('/finance-records', FinanceRecordController::class)->middleware(['role:Manager']);
});
