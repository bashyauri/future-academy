<?php

namespace App\Services\PerformanceBoost;

use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Performance optimization wrapper for Spatie QueryBuilder
 * Provides convenient methods for common optimization patterns
 */
class QueryOptimizer
{
    /**
     * Build an optimized query with allowed filters, sorts, and fields
     */
    public static function optimize(
        Builder $query,
        array $allowedFilters = [],
        array $allowedSorts = [],
        array $allowedFields = [],
        array $allowedIncludes = []
    ): QueryBuilder {
        $builder = QueryBuilder::for($query);

        if (!empty($allowedFilters)) {
            $builder->allowedFilters($allowedFilters);
        }

        if (!empty($allowedSorts)) {
            $builder->allowedSorts($allowedSorts);
        }

        if (!empty($allowedFields)) {
            $builder->allowedFields($allowedFields);
        }

        if (!empty($allowedIncludes)) {
            $builder->allowedIncludes($allowedIncludes);
        }

        return $builder;
    }

    /**
     * Get paginated results with optimization
     */
    public static function paginate(
        QueryBuilder $builder,
        int $perPage = 15,
        string $pageName = 'page',
        int $page = null
    ) {
        return $builder->paginate(
            perPage: $perPage,
            pageName: $pageName,
            page: $page
        );
    }

    /**
     * Get all results with optimization
     */
    public static function get(QueryBuilder $builder): Collection
    {
        return $builder->get();
    }

    /**
     * Get first result
     */
    public static function first(QueryBuilder $builder)
    {
        return $builder->first();
    }

    /**
     * Get count of results
     */
    public static function count(QueryBuilder $builder): int
    {
        return $builder->count();
    }
}
