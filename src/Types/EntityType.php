<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Fields\EloquentField;
use Bakery\Traits\FiltersQueries;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Bakery\Types\Definitions\EloquentType;
use Illuminate\Database\Eloquent\Relations;

class EntityType extends EloquentType
{
    use FiltersQueries;

    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename();
    }

    /**
     * Get the fields of the entity type.
     *
     * @return array
     */
    public function fields(): array
    {
        return collect()
            ->merge($this->getRegularFields())
            ->merge($this->getRelationFields())
            ->toArray();
    }

    /**
     * Get the regular fields for the entity.
     *
     * @return Collection
     */
    protected function getRegularFields(): Collection
    {
        $fields = collect();

        foreach ($this->modelSchema->getFields() as $key => $field) {
            if ($field instanceof PolymorphicField) {
                $fields = $fields->merge($this->getFieldsForPolymorphicField($key, $field));
            } else {
                $fields->put($key, $field);
            }
        }

        return $fields;
    }

    /**
     * Get the relation fields of the entity.
     *
     * @return Collection
     */
    protected function getRelationFields(): Collection
    {
        $fields = collect();

        $relations = $this->modelSchema->getRelationFields();

        foreach ($relations as $key => $field) {
            if ($field instanceof EloquentField) {
                $fields = $fields->merge($this->getFieldsForRelation($key, $field));
            } elseif ($field instanceof PolymorphicField) {
                $fields = $fields->merge($this->getFieldsForPolymorphicField($key, $field));
            }
        }

        return $fields;
    }

    /**
     * Get the fields for a relation.
     *
     * @param string $key
     * @param \Bakery\Fields\EloquentField $field
     * @return Collection
     */
    protected function getFieldsForRelation(string $key, EloquentField $field): Collection
    {
        $fields = collect();
        $relationship = $this->model->{$key}();

        if ($field->isList()) {
            $fields = $fields->merge($this->getPluralRelationFields($key, $field));
        } else {
            $fields = $fields->merge($this->getSingularRelationFields($key, $field));
        }

        if ($relationship instanceof Relations\BelongsToMany) {
            $fields = $fields->merge(
                $this->getBelongsToManyRelationFields($key, $field, $relationship)
            );
        }

        return $fields;
    }

    /**
     * Get the fields for a plural relation.
     *
     * @param string $key
     * @param \Bakery\Fields\EloquentField $field
     * @return Collection
     */
    protected function getPluralRelationFields(string $key, EloquentField $field): Collection
    {
        $fields = collect();
        $singularKey = str_singular($key);

        $field = $field->args([
            'filter' => $this->registry->type($field->getName().'Filter')->nullable(),
        ])->resolve(function ($model, $args) use ($key) {
            $relation = $model->{$key}();

            $result = $args ? $this->getRelationQuery($relation, $args)->get() : $model->{$key};

            return $result;
        });

        $fields->put($key, $field);

        $fields->put($singularKey.'Ids', $this->registry->field($this->registry->ID())
            ->list()
            ->args($field->getArgs())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function ($model, $args) use ($key) {
                $relation = $model->{$key}();

                $result = $args ? $this->getRelationQuery($relation, $args)->get() : $model->{$key};

                return $result->pluck($relation->getRelated()->getKeyName());
            })
        );

        $fields->put($key.'_count', $this->registry->field($this->registry->int())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function ($model, $args) use ($key) {
                $relation = $model->{$key};

                $result = $args ? $this->getRelationQuery($relation, $args) : $model->{$key};

                return $result->count();
            })
        );

        return $fields;
    }

    /**
     * Get the fields for a singular relation.
     *
     * @param string $key
     * @param \Bakery\Fields\EloquentField $field
     * @return Collection
     */
    protected function getSingularRelationFields(string $key, EloquentField $field): Collection
    {
        $fields = collect();

        $fields->put($key, $field);

        return $fields->put($key.'Id', $this->registry->field($this->registry->ID())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function ($model) use ($key) {
                $relation = $model->{$key};

                return $relation ? $relation->getKey() : null;
            })
        );
    }

    /**
     * Get the fields for a belongs to many relation.
     *
     * @param string $key
     * @param \Bakery\Fields\EloquentField $field
     * @param Relations\BelongsToMany $relation
     * @return Collection
     */
    protected function getBelongsToManyRelationFields(string $key, EloquentField $field, Relations\BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();

        if (! $this->registry->hasSchemaForModel($pivot)) {
            return $fields;
        }

        $pivotKey = camel_case(str_singular($key)).'Pivot';
        $modelSchema = $this->registry->resolveSchemaForModel($pivot);

        $related = $relation->getRelated();
        $inverseRelation = $related->{str_plural(class_basename($this->model))}();
        $accessor = $inverseRelation->getPivotAccessor();

        $fields->put($pivotKey, $this->registry->field($modelSchema->typename())
            ->resolve(function ($model) use ($accessor) {
                return $model->{$accessor};
            })
        );

        return $fields;
    }

    /**
     * Get the fields for a polymorphic relation.
     *
     * @param string $key
     * @param \Bakery\Fields\PolymorphicField $field
     * @return Collection
     */
    public function getFieldsForPolymorphicField(string $key, PolymorphicField $field): Collection
    {
        $typename = Utils::typename($key).'On'.$this->modelSchema->typename();

        return collect([$key => $field->setName($typename)]);
    }

    /**
     * Get the query for the given relationship.
     *
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param array $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getRelationQuery(Relations\Relation $relation, array $args)
    {
        $query = $relation->getQuery();

        if (array_key_exists('filter', $args)) {
            $query = $this->applyFilters($query, $args['filter']);
        }

        return $query;
    }
}
