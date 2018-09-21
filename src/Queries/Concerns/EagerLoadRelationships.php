<?php

namespace Bakery\Queries\Concerns;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Builder;

trait EagerLoadRelationships
{
    /**
     * @var \Bakery\TypeRegistry
     */
    protected $registry;

    /**
     * Eager load the relations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields
     * @param \Bakery\Eloquent\ModelSchema $schema
     * @param string $path
     */
    protected function eagerLoadRelations(Builder $query, array $fields, $schema, $path = '')
    {
        $relations = $schema->getRelations()->keys()->toArray();

        foreach ($fields as $key => $field) {
            if (in_array($key, $relations)) {
                $relation = $path ? $path.'.'.$key : $key;
                $query->with($relation);
                $related = $schema->getModel()->{$key}()->getRelated();
                $relatedSchema = $this->registry->getSchemaForModel($related);
                $this->eagerLoadRelations($query, $field, $relatedSchema, $relation);
            }
        }
    }
}
