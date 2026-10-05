<?php

use App\Http\Middleware\ApplyImpersonation;
use App\Http\Middleware\EnforceSingleSession;
use App\Http\Middleware\EnsureStudentRole;
use App\Http\Middleware\EnsureSubscriptionOrTrial;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\McpAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withProviders([
        //
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
            'force.json' => ForceJsonResponse::class,
        ]);

        // Exclude webhook routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'mcp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                return null;
            }

            if ($request->expectsJson()) {
                $message = match (true) {
                    $e instanceof \Illuminate\Auth\AuthenticationException => 'You need to be logged in to access this resource.',
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 'You do not have permission to perform this action.',
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 'The requested resource was not found.',
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 'The requested URL was not found.',
                    $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => 'Access denied. You do not have permission to access this resource.',
                    default => config('app.debug')
                        ? $e->getMessage()
                        : 'An error occurred while processing your request. Please try again.',
                };

                $statusCode = match (true) {
                    $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 404,
                    $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => 403,
                    $e instanceof HttpException => $e->getStatusCode(),
                    default => 500,
                };

                return response()->json([
                    'message' => $message,
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], $statusCode);
            }
        });
    })->create();
