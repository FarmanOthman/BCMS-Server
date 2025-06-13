<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarController; // Added import
use App\Http\Controllers\Api\MakeController; // Corrected use statement
use App\Http\Controllers\Api\ModelController; // Corrected use statement

Route::prefix('bcms')->group(function () {
    // Routes requiring authentication and manager role for user management
    Route::middleware(['auth:api', 'role:Manager'])->group(function () {
        // Complete CRUD for users (only accessible by managers)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'createUser']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Sign out (requires user to be authenticated to invalidate their Supabase session via token)
        Route::post('/auth/signout', [AuthController::class, 'signOut']);    });
    
    // Public authentication routes
    Route::post('/auth/signin', [AuthController::class, 'signIn'])
        ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
    
    // Get current user info (requires authentication)
    Route::get('/auth/user', [AuthController::class, 'getUser'])
        ->middleware('auth:api');
    
    // Public endpoint for listing cars (index already exists)
    // Route::get('/cars', [CarController::class, 'index']); // This was already present

    // CRUD operations for Cars - Restricted to Managers
    Route::apiResource('/cars', CarController::class)->middleware(['auth:api', 'role:Manager']);

    // API resources for Makes and Models, restricted to Managers
    Route::apiResource('/makes', MakeController::class)->middleware(['auth:api', 'role:Manager']);
    Route::apiResource('/models', ModelController::class)->middleware(['auth:api', 'role:Manager']);
});
