<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanCommandController extends Controller
{
    /**
     * Execute an artisan command via HTTP request.
     *
     * Best practice: This endpoint is disabled by default in production.
     * Prefer the Filament super-admin GUI page.
     */
    public function execute(Request $request)
    {
        // Block in production unless explicitly allowed
        if (app()->isProduction() && ! (bool) config('maintenance.allow_http_endpoint')) {
            abort(404);
        }

        // Security: allow either super-admin session OR valid token
        $user = auth()->user();
        $isSuperAdmin = $user && method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false;

        if (! $isSuperAdmin) {
            $token = $request->query('token');
            $expectedToken = env('APP_ARTISAN_TOKEN');

            if (!$expectedToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'APP_ARTISAN_TOKEN not configured in environment',
                ], 500);
            }

            if ($token !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Super-admin session or valid token required',
                ], 401);
            }
        }

        // Get command from query
        $command = $request->query('command', 'optimize');
        $allowedCommands = [
            'optimize',
            'optimize:clear',
            'cache:clear',
            'config:clear',
            'view:clear',
            'route:clear',
            'event:clear',
            'migrate',
            'migrate:rollback',
            'seed',
            'storage:link',
            'queue:restart',
        ];

        if (!in_array($command, $allowedCommands)) {
            return response()->json([
                'success' => false,
                'message' => "Command '$command' not allowed. Allowed: " . implode(', ', $allowedCommands),
            ], 403);
        }

        try {
            // Capture output
            $output = new BufferedOutput();
            // Force flag for DB-affecting commands in production
            $params = [];
            if (in_array($command, ['migrate', 'migrate:rollback', 'db:seed'])) {
                $params['--force'] = true;
            }

            \Artisan::call($command, $params, $output);
            $result = $output->fetch();

            \Log::info('HTTP artisan executed', [
                'user_id' => $user?->id,
                'command' => $command,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'command' => $command,
                'output' => $result,
            ], 200);
        } catch (\Exception $e) {
            \Log::warning('HTTP artisan failed', [
                'user_id' => $user?->id,
                'command' => $command,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
