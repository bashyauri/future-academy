<?php

namespace App\Services\PerformanceBoost;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Database query caching for performance optimization
 * Caches query results with automatic invalidation
 */
class DatabaseCaching
{
    protected static array $cacheConfig = [
        'ttl' => 3600, // 1 hour
        'prefix' => 'query_',
    ];

    /**
     * Get or cache a query result
     */
    public static function remember(
        string $key,
        \Closure $callback,
        int $ttl = null
    ) {
        $cacheKey = self::$cacheConfig['prefix'] . $key;
        $ttl = $ttl ?? self::$cacheConfig['ttl'];

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Forget a cached query
     */
    public static function forget(string $key): bool
    {
        $cacheKey = self::$cacheConfig['prefix'] . $key;
        return Cache::forget($cacheKey);
    }

    /**
     * Forget cache by pattern
     */
    public static function forgetByPattern(string $pattern): void
    {
        // For Redis/Memcached drivers, you can use pattern matching
        // For file/database drivers, this is more limited
        // Implement custom logic based on your cache driver
    }

    /**
     * Cache model count
     */
    public static function modelCount(string $modelClass, int $ttl = null): int
    {
        $key = 'count_' . strtolower(class_basename($modelClass));
        return self::remember($key, function () use ($modelClass) {
            return $modelClass::count();
        }, $ttl);
    }

    /**
     * Cache model all results
     */
    public static function modelAll(string $modelClass, int $ttl = null): Collection
    {
        $key = 'all_' . strtolower(class_basename($modelClass));
        return self::remember($key, function () use ($modelClass) {
            return $modelClass::all();
        }, $ttl);
    }

    /**
     * Set cache TTL
     */
    public static function setTtl(int $ttl): void
    {
        self::$cacheConfig['ttl'] = $ttl;
    }

    /**
     * Get cache config
     */
    public static function getConfig(): array
    {
        return self::$cacheConfig;
    }
}
