<?php

use App\Http\Middleware\ApplyImpersonation;
use App\Http\Middleware\EnforceSingleSession;
use App\Http\Middleware\EnsureStudentRole;
use App\Http\Middleware\EnsureSubscriptionOrTrial;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\McpAuth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
                    $e instanceof AuthenticationException => 'You need to be logged in to access this resource.',
                    $e instanceof AuthorizationException => 'You do not have permission to perform this action.',
                    $e instanceof ModelNotFoundException => 'The requested resource was not found.',
                    $e instanceof NotFoundHttpException => 'The requested URL was not found.',
                    $e instanceof AccessDeniedHttpException => 'Access denied. You do not have permission to access this resource.',
                    default => config('app.debug')
                        ? $e->getMessage()
                        : 'An error occurred while processing your request. Please try again.',
                };

                $statusCode = match (true) {
                    $e instanceof AuthenticationException => 401,
                    $e instanceof AuthorizationException => 403,
                    $e instanceof ModelNotFoundException => 404,
                    $e instanceof NotFoundHttpException => 404,
                    $e instanceof AccessDeniedHttpException => 403,
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
