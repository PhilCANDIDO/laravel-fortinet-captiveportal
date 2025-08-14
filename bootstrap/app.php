<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            'check.mfa' => \App\Http\Middleware\CheckMfa::class,
            'check.session.timeout' => \App\Http\Middleware\CheckSessionTimeout::class,
            'check.password.expiration' => \App\Http\Middleware\CheckPasswordExpiration::class,
            'prevent.concurrent' => \App\Http\Middleware\PreventConcurrentSessions::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'locale' => \App\Http\Middleware\SetLocale::class,
        ]);
        
        $middleware->web([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
            
            // Check if the request is for admin area
            if ($request->is('admin/*') || $request->is('admin')) {
                return redirect()->guest(route('admin.login'));
            }
            
            return redirect()->guest(route('login'));
        });
    })->create();
