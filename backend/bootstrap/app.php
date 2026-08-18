<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only application (sdd.md §1) — no session/CSRF middleware
        // needed on the api group; Passport bearer tokens are stateless.
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // failed_doc.md §1: a bare `auth:api` only checks that the bearer
        // token is valid — it does NOT re-check `is_active` on every
        // request, so a deactivated user's still-unexpired token would
        // keep working. Every module route uses this combined group
        // instead of bare `auth:api` so that check can never be forgotten
        // module-by-module. `EnsureUserIsActive` runs after `auth:api`
        // resolves $request->user(), because array order = execution
        // order for middleware listed on the same route/group.
        $middleware->group('auth.api', [
            'auth:api',
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // sdd.md §6 / failed_doc.md §8: every 4xx/5xx response uses the
        // shared {message, errors} envelope and never leaks stack traces,
        // SQL text, or file paths when APP_DEBUG=false.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return app(\App\Exceptions\ApiExceptionRenderer::class)->render($e, $request);
        });
    })->create();
