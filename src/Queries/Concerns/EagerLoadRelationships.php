<?php

namespace Bakery\Queries\Concerns;

use Bakery\Eloquent\ModelSchema;
use Bakery\Fields\EloquentField;
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
     * @param  Builder  $query
     * @param  bool[]|array  $fields
     * @param  ModelSchema  $schema
     * @param  string  $path
     */
    protected function eagerLoadRelations(Builder $query, array $fields, ModelSchema $schema, $path = '')
    {
        foreach ($fields as $key => $subFields) {
            $field = $schema->getFieldByKey($key);

            // Skip if this is not a defined field.
            if (! $field) {
                continue;
            }

            // If a custom relation resolver is provided we cannot eager load.
            if ($field instanceof EloquentField && $field->hasRelationResolver()) {
                continue;
            }

            $with = array_map(function ($with) use ($path) {
                return $path ? "{$path}.{$with}" : $with;
            }, $field->getWith() ?? []);

            $query->with($with);

            if ($field instanceof EloquentField) {
                $accessor = $field->getAccessor();
                $related = $field->getRelation($schema->getModel())->getRelated();
                $relatedSchema = $this->registry->getSchemaForModel($related);
                $this->eagerLoadRelations($query, $subFields, $relatedSchema, $path ? "{$path}.{$accessor}" : $accessor);
            }
        }
    }
}
