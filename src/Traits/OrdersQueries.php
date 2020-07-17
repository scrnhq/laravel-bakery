<?php

namespace Bakery\Traits;

use Bakery\Support\Arguments;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property ModelSchema $modelSchema
 * @property TypeRegistry $registry
 */
trait OrdersQueries
{
    /**
     * Apply ordering on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Arguments $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOrderBy(Builder $query, Arguments $args): Builder
    {
        foreach ($args as $key => $value) {
            $field = $this->modelSchema->getFieldByKey($key);

            $column = $field->getAccessor();
            $query->orderBy($column, $value);
        }

        return $query;
    }
}
