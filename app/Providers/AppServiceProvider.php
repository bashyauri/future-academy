<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
                    \Log::error('Failed to sync Bunny video analytics');
                })
                ->onSuccess(function () {
                    \Log::info('Successfully synced Bunny video analytics');
                });
        }
    }
}
