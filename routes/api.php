<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('bcms')->group(function () {
    // Routes requiring authentication and role check (e.g., for user management by a Manager)
    Route::middleware(['auth:api', 'role.manager'])->group(function () { // Changed auth:sanctum to auth:api and role.user to role.manager
        Route::post('/users', [UserController::class, 'createUser'])
            ->can('create', App\Models\User::class); // Policy/Gate check

        // Example route to get current user (me) - also apply middleware if it needs role
        // Route::get('/me', [UserController::class, 'me']);

        // Sign out (requires user to be authenticated to invalidate their Supabase session via token)
        Route::post('/auth/signout', [AuthController::class, 'signOut']);
    });

    // Public authentication routes
    Route::post('/auth/signin', [AuthController::class, 'signIn'])
        ->middleware('throttle:5,1'); // Rate limit: 5 attempts per minute

    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);
});
