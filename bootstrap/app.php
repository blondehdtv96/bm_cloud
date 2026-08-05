<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
        ]);
        // Note: statefulApi() is intentionally NOT enabled. This app authenticates
        // purely via Sanctum Bearer tokens (see AuthController::login / useApi.js),
        // not cookie/session-based SPA auth. Enabling it forces CSRF validation on
        // API routes for "stateful" domains (e.g. localhost:8000) even though the
        // frontend never performs the /sanctum/csrf-cookie handshake, which caused
        // every POST request (including /api/login) to fail with "CSRF token mismatch".
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();