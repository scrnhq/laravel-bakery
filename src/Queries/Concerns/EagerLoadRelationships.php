<?php

namespace Bakery\Queries\Concerns;

use Bakery\Fields\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Illuminate\Database\Eloquent\Builder;

trait EagerLoadRelationships
{
    /**
     * @var TypeRegistry
     */
    protected $registry;

    /**
     * Eager load the relations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields
     * @param ModelSchema $schema
     * @param string $path
     */
    protected function eagerLoadRelations(Builder $query, array $fields, ModelSchema $schema, $path = '')
    {
        $relations = $schema->getRelations()->keys()->toArray();

        foreach ($fields as $key => $field) {
            if (in_array($key, $relations)) {
                $relationField = $this->getRelationFieldByKey($key, $schema);
                $column = $relationField->getAccessor();
                $eagerLoadPath = $path ? $path.'.'.$column : $column;
                $query->with($eagerLoadPath);
                $related = $schema->getModel()->{$column}()->getRelated();
                $relatedSchema = $this->registry->getSchemaForModel($related);
                $this->eagerLoadRelations($query, $field, $relatedSchema, $eagerLoadPath);
            }
        }
    }

    /**
     * Get a relation field by it's key.
     *
     * @param string $key
     * @param ModelSchema $schema
     * @return Field
     */
    protected function getRelationFieldByKey(string $key, ModelSchema $schema): Field
    {
        return $schema->getRelationFields()->first(function (Field $field, $relation) use ($key) {
            return $relation === $key;
        });
    }
}
