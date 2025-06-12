<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController; // Added import
use App\Http\Controllers\Api\MakeController; // Corrected use statement
use App\Http\Controllers\Api\ModelController; // Corrected use statement

Route::prefix('bcms')->group(function () {
    // Routes requiring authentication and role check (e.g., for user management by a Manager)
    Route::middleware(['auth:api', 'role.manager'])->group(function () { // Changed auth:sanctum to auth:api and role.user to role.manager
        Route::post('/users', [UserController::class, 'createUser'])
            ->can('create', App\Models\User::class); // Policy/Gate check

        // Sign out (requires user to be authenticated to invalidate their Supabase session via token)
        Route::post('/auth/signout', [AuthController::class, 'signOut']);
    });

    // Public authentication routes
    Route::post('/auth/signin', [AuthController::class, 'signIn'])
        ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
    
    // Public endpoint for listing cars
    Route::get('/cars', [CarController::class, 'index']);

    // API resources for Makes and Models, restricted to Managers
    Route::apiResource('/makes', MakeController::class)->middleware(['auth:api', 'role.manager']);
    Route::apiResource('/models', ModelController::class)->middleware(['auth:api', 'role.manager']);
});
