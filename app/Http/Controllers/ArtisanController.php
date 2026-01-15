<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceCommandType;
use App\Models\MaintenanceAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanController extends Controller
{
    /**
     * Execute artisan command via web route.
     * Protected by token authentication for security.
     */
    public function execute(Request $request, string $command)
    {
        // Verify token
        $token = $request->query('token') ?? $request->header('X-Artisan-Token');

        if ($token !== config('app.artisan_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid token.',
            ], 403);
        }

        // Verify command is allowed
        $commandType = MaintenanceCommandType::tryFrom($command);

        if (!$commandType) {
            return response()->json([
                'success' => false,
                'message' => "Command not allowed: {$command}",
            ], 400);
        }

        try {
            $buffer = new BufferedOutput();

            // Block DB-affecting commands in production unless explicitly allowed
            if (app()->isProduction() && $commandType->requiresForce() && !config('maintenance.allow_db_commands')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blocked in production. Set ALLOW_DB_COMMANDS=true to enable.',
                ], 403);
            }

            // Provide --force for DB commands to run non-interactively
            $params = [];
            if ($commandType->requiresForce()) {
                $params['--force'] = true;
            }

            Artisan::call($command, $params, $buffer);
            $output = $buffer->fetch() ?: 'Command executed successfully.';

            // Audit log
            MaintenanceAction::create([
                'user_id' => auth()->id(),
                'command' => $command,
                'status' => 'success',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'output' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command executed successfully.',
                'output' => $output,
                'command' => $command,
                'executed_at' => now()->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {
            $errorMessage = 'Error: ' . $e->getMessage();

            // Audit log (error)
            MaintenanceAction::create([
                'user_id' => auth()->id(),
                'command' => $command,
                'status' => 'error',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'output' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Command failed.',
                'error' => $e->getMessage(),
                'command' => $command,
            ], 500);
        }
    }
}
