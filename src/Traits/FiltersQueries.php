<?php

namespace Bakery\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Grammars;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \Bakery\Eloquent\ModelSchema $modelSchema
 * @property \Bakery\Support\TypeRegistry $registry
 */
trait FiltersQueries
{
    /**
     * Filter the query based on the filter argument.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters(Builder $query, array $args): Builder
    {
        // We wrap the query in a closure to make sure it
        // does not clash with other (scoped) queries that are on the builder.
        return $query->where(function ($query) use ($args) {
            return $this->applyFiltersRecursively($query, $args);
        });
    }

    /**
     * Apply filters recursively.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $args
     * @param mixed $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFiltersRecursively(Builder $query, array $args, $type = null): Builder
    {
        foreach ($args as $key => $value) {
            if ($key === 'AND' || $key === 'OR') {
                $query->where(function ($query) use ($value, $key) {
                    foreach ($value as $set) {
                        if (! empty($set)) {
                            $this->applyFiltersRecursively($query, $set ?? [], $key);
                        }
                    }
                });
            } else {
                $schema = $this->registry->resolveSchemaForModel(get_class($query->getModel()));

                if ($schema->getRelationFields()->has($key)) {
                    $this->applyRelationFilter($query, $key, $value, $type);
                } else {
                    $this->filter($query, $key, $value, $type);
                }
            }
        }

        return $query;
    }

    /**
     * Filter the query based on the filter argument that contain relations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $relation
     * @param array $args
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRelationFilter(Builder $query, string $relation, $args, $type): Builder
    {
        $count = 1;
        $operator = '>=';
        $type = $type ?: 'and';

        if (! $args) {
            return $query->doesntHave($relation, $type);
        }

        return $query->has($relation, $operator, $count, $type, function ($subQuery) use ($args) {
            return $this->applyFiltersRecursively($subQuery, $args);
        });
    }

    /**
     * Filter the query by a key and value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key
     * @param mixed $value
     * @param string $type (AND or OR)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filter(Builder $query, string $key, $value, $type): Builder
    {
        $type = $type ?: 'AND';

        $likeOperator = $this->getCaseInsensitiveLikeOperator();

        $table = $query->getModel()->getTable().'.';

        if (ends_with($key, '_not_contains')) {
            $key = str_before($key, '_not_contains');
            $query->where($key, 'NOT '.$likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($table.$key, '_contains')) {
            $key = str_before($key, '_contains');
            $query->where($table.$key, $likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, '_not_starts_with')) {
            $key = str_before($key, '_not_starts_with');
            $query->where($table.$key, 'NOT '.$likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_starts_with')) {
            $key = str_before($key, '_starts_with');
            $query->where($table.$key, $likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_not_ends_with')) {
            $key = str_before($key, '_not_ends_with');
            $query->where($table.$key, 'NOT '.$likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_ends_with')) {
            $key = str_before($key, '_ends_with');
            $query->where($table.$key, $likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_not')) {
            $key = str_before($key, '_not');
            $query->where($table.$key, '!=', $value, $type);
        } elseif (ends_with($key, '_not_in')) {
            $key = str_before($key, '_not_in');
            $query->whereNotIn($table.$key, $value, $type);
        } elseif (ends_with($key, '_in')) {
            $key = str_before($key, '_in');
            $query->whereIn($table.$key, $value, $type);
        } elseif (ends_with($key, '_lt')) {
            $key = str_before($key, '_lt');
            $query->where($table.$key, '<', $value, $type);
        } elseif (ends_with($key, '_lte')) {
            $key = str_before($key, '_lte');
            $query->where($table.$key, '<=', $value, $type);
        } elseif (ends_with($key, '_gt')) {
            $key = str_before($key, '_gt');
            $query->where($table.$key, '>', $value, $type);
        } elseif (ends_with($key, '_gte')) {
            $key = str_before($key, '_gte');
            $query->where($table.$key, '>=', $value, $type);
        } else {
            $query->where($table.$key, '=', $value, $type);
        }

        return $query;
    }

    /**
     * Check if the current database grammar supports the insensitive like operator.
     *
     * @return string
     */
    protected function getCaseInsensitiveLikeOperator()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        return $connection->getQueryGrammar() instanceof Grammars\PostgresGrammar ? 'ILIKE' : 'LIKE';
    }
}
