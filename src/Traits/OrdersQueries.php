<?php

namespace Bakery\Traits;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Arguments;
use Bakery\Support\TypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

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

            if ($field->isRelationship()) {
                $relation = $field->getAccessor();
                $this->applyRelationalOrderBy($query, $this->model, $relation, $value);
            } else {
                $column = $field->getAccessor();
                $this->orderBy($query, $query->getModel()->getTable().'.'.$column, $value);
            }
        }

        return $query;
    }

    /**
     * Apply relational order by.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $relation
     * @param array $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRelationalOrderBy(Builder $query, Model $model, string $relation, Arguments $args): Builder
    {
        /** @var Relation $relation */
        $relation = $model->$relation();
        $related = $relation->getRelated();
        $query = $this->joinRelation($query, $relation, 'left');

        foreach ($args as $key => $value) {
            $schema = $this->registry->resolveSchemaForModel(get_class($related));

            $relations = $schema->getRelationFields();
            if ($relations->keys()->contains($key)) {
                $query = $this->applyRelationalOrderBy($query, $related, $key, $value);
            } else {
                $query = $this->orderBy($query, $related->getTable().'.'.$key, $value);
            }
        }

        return $query;
    }

    /**
     * Apply the ordering.
     *
     * @param Builder $query
     * @param string $column
     * @param string $ordering
     * @return Builder
     */
    protected function orderBy(Builder $query, string $column, string $ordering)
    {
        // Alias so this doesn't conflict with actually selected fields.
        $alias = 'bakery_'.str_replace('.', '_', $column);

        $query->addSelect("{$column} as {$alias}");

        return $query->orderBy($alias, $ordering);
    }
}
