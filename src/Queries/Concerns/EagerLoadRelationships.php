<?php

namespace Bakery\Queries\Concerns;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Builder;

trait EagerLoadRelationships
{
    /**
     * Eager load the relations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields
     * @param \Bakery\Eloquent\Introspectable $schema
     * @param string $path
     */
    protected function eagerLoadRelations(Builder $query, array $fields, $schema, $path = '')
    {
        $relations = $schema->getRelations()->keys()->toArray();

        foreach ($fields as $key => $field) {
            if (in_array($key, $relations)) {
                $path = $path ? $path.'.'.$key : $key;
                $query->with($path);
                $related = $schema->getModel()->{$key}()->getRelated();
                $relatedSchema = resolve(Bakery::getModelSchema($related));
                $this->eagerLoadRelations($query, $field, $relatedSchema, $path);
            }
        }
    }
}
