<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\FailoverableException;
use Modules\Auth\Infrastructure\Http\Middleware\EnsurePasswordNotExpired;
use Modules\Auth\Infrastructure\Http\Middleware\EnsureTwoFactorEnabled;
use Shared\Infrastructure\Http\Middleware\SecurityHeaders;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Route-level aliases: enforce mandatory 2FA on privileged route groups
        // (prompt §3/§8) via `two-factor.enforce`.
        $middleware->alias([
            'two-factor.enforce' => EnsureTwoFactorEnabled::class,
            'password.not-expired' => EnsurePasswordNotExpired::class,
            // spatie/laravel-permission aliases are NOT auto-registered in
            // Laravel 11+ (no Http/Kernel). Every `permission:*` / `role:*`
            // route guard depends on these.
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // SecurityHeaders runs BEFORE Inertia so its headers + CSP nonce
        // survive Inertia (SSR) responses. EnsurePasswordNotExpired forces a
        // password change once it expires (prompt §5); it self-excludes the
        // password-update / logout routes and no-ops for guests.
        $middleware->web(append: [
            SecurityHeaders::class,
            HandleInertiaRequests::class,
            EnsurePasswordNotExpired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Web module XHR (e.g. /video-export) sends Accept: application/json but
        // is not under /api/*. Keep api/* + expectsJson so throttle/validation
        // errors return JSON instead of an HTML error page.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // laravel/ai's provider-transient exceptions (overloaded/rate-limited/
        // out-of-credits) are plain Exceptions, not HttpExceptionInterface, so
        // the default handler would otherwise mask them behind a generic
        // "Server Error" 500 in production. Surface the real message as a 503
        // so the AI-assist panels (Post/Campaigns/SocialMedia) can show it —
        // they already read `body.message` from the JSON response.
        $exceptions->render(function (AiException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(
                ['message' => $e->getMessage()],
                $e instanceof FailoverableException ? 503 : 500,
            );
        });
    })->create();
