<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\\Http\\Middleware\\TrustHosts::class, // Example if you have it
        // Consider adding global middleware like TrustProxies if behind a load balancer
        // \Illuminate\Http\Middleware\TrustProxies::class => null, // Example
        // \Illuminate\Http\Middleware\HandleCors::class, // If you need CORS for your desktop app
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        // 'web' => [ // Web group not needed for a pure API for a desktop app
        //     \Illuminate\Cookie\Middleware\EncryptCookies::class,
        //     \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        //     \Illuminate\Session\Middleware\StartSession::class,
        //     \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        //     \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // ],

        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Consider adding CORS middleware here if needed globally for API
            // \App\\Http\\Middleware\\Cors::class, // Example if you have a custom one or use a package
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // 'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class, // Typically for web
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class, // Supabase handles verification
        'role' => \App\Http\Middleware\CheckRole::class,
        // role.user and role.manager are defined in bootstrap/app.php, so they are fine here
    ];
}
