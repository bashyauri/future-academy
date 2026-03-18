<?php

namespace App\Services;

use App\Services\McpServer\McpServer;
use App\Services\PerformanceBoost\QueryOptimizer;
use App\Services\PerformanceBoost\DatabaseCaching;
use App\Services\PerformanceBoost\LazyLoadHelper;
use Illuminate\Support\Facades\Log;

/**
 * Integration service for both MCP Server and Laravel Boost
 * Provides a unified interface for performance monitoring and AI tool interaction
 */
class IntegrationService
{
    protected McpServer $mcp;

    public function __construct(McpServer $mcp)
    {
        $this->mcp = $mcp;
    }

    /**
     * Get system health and performance metrics
     */
    public function getHealthMetrics(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
            ],
            'performance' => [
                'cache_enabled' => config('boost.cache.enabled'),
                'query_optimization' => config('boost.query'),
                'monitoring_enabled' => config('boost.monitoring.enabled'),
            ],
            'mcp' => [
                'enabled' => config('mcp-server.enabled'),
                'tools' => $this->mcp->getAvailableTools(),
                'resources' => $this->mcp->getAvailableResources(),
            ],
        ];
    }

    /**
     * Get project statistics
     */
    public function getProjectStats(): array
    {
        $projectInfo = $this->mcp->getProjectInfo();

        return [
            'project' => $projectInfo['name'],
            'environment' => $projectInfo['environment'],
            'laravel_version' => $projectInfo['laravel_version'],
            'php_version' => $projectInfo['php_version'],
            'models_count' => count($projectInfo['models']),
            'routes_count' => $projectInfo['routes']['total'],
        ];
    }

    /**
     * Log integration event
     */
    public function logEvent(string $event, array $data = []): void
    {
        $this->mcp->log('Integration: ' . $event, $data);
    }

    /**
     * Get integration recommendations based on project state
     */
    public function getRecommendations(): array
    {
        $metrics = $this->getHealthMetrics();
        $recommendations = [];

        // Check performance optimizations
        if (!$metrics['performance']['cache_enabled']) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'performance',
                'message' => 'Enable caching for better query performance',
                'action' => 'Set BOOST_CACHE_DRIVER in .env',
            ];
        }

        if (!$metrics['performance']['monitoring_enabled']) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'monitoring',
                'message' => 'Enable performance monitoring',
                'action' => 'Set BOOST_MONITORING=true in .env',
            ];
        }

        // Check MCP configuration
        if ($metrics['mcp']['enabled'] && !config('mcp-server.security.require_auth')) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'security',
                'message' => 'Enable MCP authentication in production',
                'action' => 'Set MCP_REQUIRE_AUTH=true and configure token',
            ];
        }

        return $recommendations;
    }

    /**
     * Example: Optimize a query with caching
     */
    public static function optimizeQueryWithCache(
        \Illuminate\Database\Eloquent\Builder $query,
        string $cacheKey,
        int $cacheTTL = 3600
    ) {
        return DatabaseCaching::remember($cacheKey, function () use ($query) {
            return QueryOptimizer::get($query);
        }, $cacheTTL);
    }

    /**
     * Example: Optimize a query with eager loading
     */
    public static function optimizeQueryWithLoading(
        \Illuminate\Database\Eloquent\Builder $query,
        array $withRelations = [],
        array $withCountRelations = []
    ) {
        return LazyLoadHelper::optimizeForList($query, $withRelations, $withCountRelations);
    }
}
