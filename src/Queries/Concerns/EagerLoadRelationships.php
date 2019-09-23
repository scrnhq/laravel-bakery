<?php

namespace Bakery\Queries\Concerns;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\TypeRegistry;
use Bakery\Fields\PolymorphicField;
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool[]|array                           $fields
     * @param  ModelSchema                            $schema
     * @param  string                                 $path
     */
    protected function eagerLoadRelations(Builder $query, array $fields, ModelSchema $schema, $path = '')
    {
        foreach ($fields as $key => $subFields) {
            $field = $schema->getFieldByKey($key);

            $with = array_map(function ($with) use ($path) {
                return $path ? "{$path}.{$with}" : $with;
            }, $field->getWith() ?? []);

            $query->with($with);

            if ($field->isRelationship() && ! $field instanceof PolymorphicField) {
                $accessor = $field->getAccessor();
                $related = $schema->getModel()->{$accessor}()->getRelated();
                $relatedSchema = $this->registry->getSchemaForModel($related);
                $this->eagerLoadRelations($query, $subFields, $relatedSchema, $path ? "{$path}.{$accessor}" : $accessor);
            }
        }
    }
}
