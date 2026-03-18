<?php

namespace App\Services\PerformanceBoost;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Laravel Boost performance utilities
 */
class BoostServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton('query-optimizer', fn () => new QueryOptimizer());
        $this->app->singleton('database-caching', fn () => new DatabaseCaching());
        $this->app->singleton('lazy-load-helper', fn () => new LazyLoadHelper());
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/boost.php' => config_path('boost.php'),
        ], 'boost-config');

        // Load custom query builder macros if any
        $this->registerMacros();
    }

    /**
     * Register helper macros
     */
    protected function registerMacros(): void
    {
        // Add custom macros to Builder
        \Illuminate\Database\Eloquent\Builder::macro('optimized', function (
            array $filters = [],
            array $sorts = [],
            array $fields = [],
            array $includes = []
        ) {
            return QueryOptimizer::optimize($this, $filters, $sorts, $fields, $includes);
        });

        // Add caching macro
        \Illuminate\Database\Eloquent\Builder::macro('rememberFor', function (
            string $key,
            int $minutes = 60
        ) {
            return DatabaseCaching::remember($key, function () {
                return $this->get();
            }, $minutes * 60);
        });
    }
}
