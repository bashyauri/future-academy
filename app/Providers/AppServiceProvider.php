<?php

namespace App\Providers;

use App\Services\McpServer\McpServer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
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
