<?php

use App\Http\Middleware\ApplyImpersonation;
use App\Http\Middleware\EnforceSingleSession;
use App\Http\Middleware\EnsureStudentRole;
use App\Http\Middleware\EnsureSubscriptionOrTrial;
use App\Http\Middleware\McpAuth;
use App\Services\PerformanceBoost\BoostServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withProviders([
        BoostServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            EnforceSingleSession::class,
            ApplyImpersonation::class,
        ]);

        $middleware->alias([
            'ensure.subscription.or.trial' => EnsureSubscriptionOrTrial::class,
            'ensure.student' => EnsureStudentRole::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'mcp.auth' => McpAuth::class,
        ]);

        // Exclude webhook routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'mcp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
