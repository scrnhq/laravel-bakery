<?php

namespace Bakery\Traits;

use Illuminate\Support\Str;
use Bakery\Support\Arguments;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use Bakery\Types\CollectionFilterType;
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
        foreach ($args as $filter => $value) {
            if ($filter === 'AND' || $filter === 'OR') {
                $query->where(function ($query) use ($value, $filter) {
                    foreach ($value as $set) {
                        if (! empty($set)) {
                            $this->applyFiltersRecursively($query, $set, $filter);
                        }
                    }
                });
            } else {
                $key = $this->getKeyForFilter($filter);
                $schema = $this->registry->resolveSchemaForModel(get_class($query->getModel()));
                $field = $schema->getFieldByKey($key);

                if ($field->isRelationship()) {
                    $relation = $field->getAccessor();
                    $this->applyRelationFilter($query, $relation, $value, $type);
                } else {
                    $column = $field->getAccessor();
                    $this->filter($query, $filter, $column, $value, $type);
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
    protected function applyRelationFilter(Builder $query, string $relation, Arguments $args = null, $type = null): Builder
    {
        $count = 1;
        $operator = '>=';
        $type = $type ?: 'and';

        if (!$args || $args->isEmpty()) {
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

        $table = $query->getModel()->getTable();
        $qualifiedColumn = $table.'.'.$column;

        $value = $value instanceof Arguments ? $value->toArray() : $value;

        if (ends_with($key, 'NotContains')) {
            $query->where($qualifiedColumn, 'NOT '.$likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, 'Contains')) {
            $query->where($qualifiedColumn, $likeOperator, '%'.$value.'%', $type);
        } elseif (ends_with($key, 'NotStartsWith')) {
            $query->where($qualifiedColumn, 'NOT '.$likeOperator, $value.'%', $type);
        } elseif (ends_with($key, 'StartsWith')) {
            $query->where($qualifiedColumn, $likeOperator, $value.'%', $type);
        } elseif (ends_with($key, 'NotEndsWith')) {
            $query->where($qualifiedColumn, 'NOT '.$likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, 'EndsWith')) {
            $query->where($qualifiedColumn, $likeOperator, '%'.$value, $type);
        } elseif (ends_with($key, 'Not')) {
            $query->where($qualifiedColumn, '!=', $value, $type);
        } elseif (ends_with($key, 'NotIn')) {
            $query->whereNotIn($qualifiedColumn, $value, $type);
        } elseif (ends_with($key, 'In')) {
            $query->whereIn($qualifiedColumn, $value, $type);
        } elseif (ends_with($key, 'LessThan')) {
            $query->where($qualifiedColumn, '<', $value, $type);
        } elseif (ends_with($key, 'LessThanOrEquals')) {
            $query->where($qualifiedColumn, '<=', $value, $type);
        } elseif (ends_with($key, 'GreaterThan')) {
            $query->where($qualifiedColumn, '>', $value, $type);
        } elseif (ends_with($key, 'GreaterThanOrEquals')) {
            $query->where($qualifiedColumn, '>=', $value, $type);
        } else {
            $query->where($qualifiedColumn, '=', $value, $type);
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

    /**
     * Get the key for a certain filter.
     * E.g. TitleStartsWith => Title.
     *
     * @param string $subject
     * @return string
     */
    protected function getKeyForFilter(string $subject): string
    {
        foreach (CollectionFilterType::$filters as $filter) {
            if (Str::endsWith($subject, $filter)) {
                return Str::before($subject, $filter);
            }
        }

        return $subject;
    }
}
