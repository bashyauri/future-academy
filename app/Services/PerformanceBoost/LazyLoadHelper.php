<?php

namespace App\Services\PerformanceBoost;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lazy loading and eager loading helpers for performance
 */
class LazyLoadHelper
{
    /**
     * Get model with eager loaded relationships
     */
    public static function with(Builder $query, array|string $relations): Builder
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        return $query->with($relations);
    }

    /**
     * Get model with counted relationships
     */
    public static function withCount(Builder $query, array|string $relations): Builder
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        return $query->withCount($relations);
    }

    /**
     * Get model with eager loaded and counted relationships
     */
    public static function withBoth(Builder $query, array $withRelations, array $withCountRelations): Builder
    {
        return $query
            ->with($withRelations)
            ->withCount($withCountRelations);
    }

    /**
     * Lazy load a relation on a collection
     */
    public static function loadRelation(iterable $models, array|string $relations)
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }

        // Handle both Collection and array
        if (method_exists($models, 'load')) {
            return $models->load($relations);
        }

        // For arrays, convert to collection, load, then back to array
        return collect($models)->load($relations)->all();
    }

    /**
     * Optimize query for list pagination
     */
    public static function optimizeForList(
        Builder $query,
        array $with = [],
        array $withCount = [],
        array $select = ['*']
    ): Builder {
        $query = $query->select($select);

        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($withCount)) {
            $query = $query->withCount($withCount);
        }

        return $query;
    }

    /**
     * Optimize query for single resource
     */
    public static function optimizeForShow(
        Builder $query,
        array $with = [],
        array $withCount = []
    ): Builder {
        if (!empty($with)) {
            $query = $query->with($with);
        }

        if (!empty($withCount)) {
            $query = $query->withCount($withCount);
        }

        return $query;
    }
}
