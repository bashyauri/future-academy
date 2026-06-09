<?php

namespace App\Providers;

use App\Models\User;
use App\Services\McpServer\McpServer;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register MCP Server as singleton
        $this->app->singleton(McpServer::class, function ($app) {
            return new McpServer;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require_once app_path('Helpers/MathLatexHelper.php');

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User || ! Schema::hasColumn('users', 'current_session_id')) {
                return;
            }

            $user = $event->user;

            $newSessionId = (string) session()->getId();
            $previousSessionId = (string) ($user->current_session_id ?? '');

            if ($previousSessionId !== '' && ! hash_equals($previousSessionId, $newSessionId)) {
                session()->getHandler()->destroy($previousSessionId);
            }

            $user->setAttribute('current_session_id', $newSessionId);
            $user->save();

            // Fortify regenerates the session id after authentication. Flag the next
            // authenticated request so middleware can sync the final session id.
            session()->put('single_session_sync_pending', true);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User || ! Schema::hasColumn('users', 'current_session_id')) {
                return;
            }

            $user = $event->user;

            if ((string) $user->current_session_id !== (string) session()->getId()) {
                return;
            }

            $user->setAttribute('current_session_id', null);
            $user->save();
        });

        // Register scheduled tasks (for Laravel 11+)
        $this->configureSchedule();
    }

    /**
     * Configure the application's scheduled commands.
     */
    protected function configureSchedule(): void
    {
        // Only register schedule in console context
        if ($this->app->runningInConsole()) {
            $schedule = $this->app->make(Schedule::class);

            // Sync Bunny video analytics daily at 2 AM
            $schedule->command('bunny:sync-analytics')->dailyAt('02:00')
                ->description('Sync video analytics from Bunny Stream API')
                ->onFailure(function () {
                    Log::error('Failed to sync Bunny video analytics');
                })
                ->onSuccess(function () {
                    Log::info('Successfully synced Bunny video analytics');
                });
        }
    }
}
