<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Fields\Field;
use Illuminate\Support\Str;
use Bakery\Support\Arguments;
use Bakery\Fields\EloquentField;
use Bakery\Traits\FiltersQueries;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Types\Definitions\EloquentType;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        $this->modelSchema->getFields()->each(function (Field $field, string $key) use ($fields) {
            if ($field instanceof PolymorphicField) {
                $fields = $fields->merge($this->getFieldsForPolymorphicField($key, $field));
            } else {
                $fields->put($key, $field);
            }
        });

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
        $relationship = $field->getRelation($this->model);

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
        $singularKey = Str::singular($key);

        $field = $field->args([
            'filter' => $this->registry->type($field->getName().'Filter')->nullable(),
        ])->resolve(function (Model $model, string $accessor, Arguments $args) use ($field) {
            $relation = $field->getRelation($model);

            return $args->isEmpty() ? $field->getResult($model) : $this->getRelationQuery($relation, $args)->get();
        });

        $fields->put($key, $field);

        $fields->put($singularKey.'Ids', $this->registry->field($this->registry->ID())
            ->list()
            ->accessor($field->getAccessor())
            ->args($field->getArgs())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function (Model $model, string $accessor, Arguments $args) use ($field) {
                $relation = $field->getRelation($model);

                $query = $args->isEmpty() ? $relation : $this->getRelationQuery($relation, $args);

                return $query->pluck($relation->getRelated()->getKeyName());
            })
        );

        $fields->put($key.'Count', $this->registry->field($this->registry->int())
            ->accessor($field->getAccessor())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function (Model $model, string $accessor, Arguments $args) use ($field) {
                $relation = $field->getRelation($model);

                $query = $args->isEmpty() ? $relation : $this->getRelationQuery($relation, $args);

                return $query->count();
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
            ->accessor($field->getAccessor())
            ->nullable($field->isNullable())
            ->viewPolicy($field->getViewPolicy())
            ->resolve(function (Model $model, string $accessor) {
                $relation = $model->{$accessor};

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

        $pivotKey = Utils::single($key).'Pivot';
        $modelSchema = $this->registry->resolveSchemaForModel($pivot);

        $related = $relation->getRelated();

        if (! $field->getInverse()) {
            $modelClassName = class_basename($this->model);
            $relatedClassName = class_basename($related);
            $guess = Utils::plural($modelClassName);

            Utils::invariant(
                method_exists($related, $guess),
                "Failed to guess the inverse relationship for `${key}` on `${modelClassName}`.\n".
                "Guessed `${guess}` but could not find such relationship on `${relatedClassName}`.\n".
                "You can specify the inverse relationship by calling the `inverse('relationName')` on the field."
            );

            $inverseRelationName = $guess;
        } else {
            $inverseRelationName = $field->getInverse();
        }

        $inverseRelation = $related->{$inverseRelationName}();
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
     * @param Relation $relation
     * @param Arguments $args
     * @return Builder
     */
    protected function getRelationQuery(Relation $relation, Arguments $args)
    {
        $query = $relation->getQuery();

        if ($args->filter) {
            $query = $this->applyFilters($query, $args->filter);
        }

        return $query;
    }
}
