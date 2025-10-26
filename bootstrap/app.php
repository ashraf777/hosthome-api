<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // --- ADD THE CUSTOM ALIASES HERE ---
        $middleware->alias([
            // Alias for your custom, package-free authentication middleware
            'api.token.check' => App\Http\Middleware\CheckApiToken::class, 
            
            // Register the Laravel built-in 'can' middleware for authorization checks
            // This is crucial for using ->middleware('can:role:manage') in your routes
            'can' => Illuminate\Auth\Middleware\Authorize::class, 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
