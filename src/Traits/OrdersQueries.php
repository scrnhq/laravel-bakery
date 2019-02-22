<?php

namespace Bakery\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \Bakery\Eloquent\ModelSchema $modelSchema
 * @property \Bakery\Support\TypeRegistry $registry
 */
trait OrdersQueries
{
    /**
     * Apply ordering on the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyOrderBy(Builder $query, array $args): Builder
    {
        $relations = $this->modelSchema->getRelationFields();
        foreach ($args as $key => $value) {
            if ($relations->keys()->contains($key)) {
                $this->applyRelationalOrderBy($query, $this->model, $key, $value);
            } else {
                $this->orderBy($query, $query->getModel()->getTable().'.'.$key, $value);
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
    protected function applyRelationalOrderBy(Builder $query, Model $model, string $relation, array $args): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
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
        $query->addSelect($column);

        return $query->orderBy($column, $ordering);
    }
}
