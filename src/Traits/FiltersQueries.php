<?php

namespace Bakery\Traits;

use Bakery\Fields\Field;
use Bakery\Support\Arguments;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property ModelSchema $modelSchema
 * @property TypeRegistry $registry
 */
trait FiltersQueries
{
    /**
     * Filter the query based on the filter argument.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Arguments $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters(Builder $query, Arguments $args): Builder
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
     * @param Arguments $args
     * @param mixed $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFiltersRecursively(Builder $query, Arguments $args = null, $type = null): Builder
    {
        foreach ($args as $key => $value) {
            if ($key === 'AND' || $key === 'OR') {
                $query->where(function ($query) use ($value, $key) {
                    foreach ($value as $set) {
                        if ( ! empty($set)) {
                            $this->applyFiltersRecursively($query, $set, $key);
                        }
                    }
                });
            } else {
                $schema = $this->registry->resolveSchemaForModel(get_class($query->getModel()));

                if ($schema->getRelationFields()->has($key)) {
                    // TODO: Extract this.
                    $relation = $schema->getRelationFields()->first(function (Field $field, string $fieldKey) use ($key) {
                        return $key === $fieldKey;
                    })->getAccessor();

                    $this->applyRelationFilter($query, $relation, $value, $type);
                } else {
                    // TODO: Extract this.
                    $column = $schema->getFields()->first(function (Field $field, string $fieldKey) use ($key) {
                        return $key === $fieldKey;
                    })->getAccessor();

                    $this->filter($query, $key, $column, $value, $type);
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
     * @param Arguments $args
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRelationFilter(Builder $query, string $relation, Arguments $args, $type): Builder
    {
        $count = 1;
        $operator = '>=';
        $type = $type ?: 'and';

        if ($args->isEmpty()) {
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
     * @param string $column
     * @param mixed $value
     * @param string $type (AND or OR)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function filter(Builder $query, string $key, string $column, $value, $type): Builder
    {
        $type = $type ?: 'AND';

        $likeOperator = $this->getCaseInsensitiveLikeOperator();

        $table = $query->getModel()->getTable().'.';

        if (ends_with($key, '_not_contains')) {
            $query->where($column, 'NOT '.$likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, '_contains')) {
            $query->where($table.$column, $likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, '_not_starts_with')) {
            $query->where($table.$column, 'NOT '.$likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_starts_with')) {
            $query->where($table.$column, $likeOperator, $value.'%', $type);
        } elseif (ends_with($key, '_not_ends_with')) {
            $query->where($table.$column, 'NOT '.$likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_ends_with')) {
            $query->where($table.$column, $likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, '_not')) {
            $query->where($table.$column, '!=', $value, $type);
        } elseif (ends_with($key, '_not_in')) {
            $query->whereNotIn($table.$column, $value, $type);
        } elseif (ends_with($key, '_in')) {
            $query->whereIn($table.$column, $value, $type);
        } elseif (ends_with($key, '_lt')) {
            $query->where($table.$column, '<', $value, $type);
        } elseif (ends_with($key, '_lte')) {
            $query->where($table.$column, '<=', $value, $type);
        } elseif (ends_with($key, '_gt')) {
            $query->where($table.$column, '>', $value, $type);
        } elseif (ends_with($key, '_gte')) {
            $query->where($table.$column, '>=', $value, $type);
        } else {
            $query->where($table.$column, '=', $value, $type);
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
        /** @var Connection $connection */
        $connection = DB::connection();

        return $connection->getQueryGrammar() instanceof Grammars\PostgresGrammar ? 'ILIKE' : 'LIKE';
    }
}
