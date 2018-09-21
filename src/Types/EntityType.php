<?php

namespace Bakery\Types;

use Closure;
use Bakery\Utils\Utils;
use Bakery\Fields\EloquentField;
use Illuminate\Support\Collection;
use Bakery\Fields\PolymorphicField;
use Bakery\Types\Definitions\EloquentType;
use Illuminate\Database\Eloquent\Relations;

class EntityType extends EloquentType
{
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
     * Create the field resolver.
     *
     * @param array $field
     * @param string $key
     * @return Closure
     */
    protected function createFieldResolver(array $field, string $key): Closure
    {
        return function ($source, $args, $context) use ($key, $field) {
            if (array_key_exists('policy', $field)) {
                $this->checkPolicy($field, $key, $source, $args);
            }

            if (array_key_exists('resolve', $field)) {
                return $field['resolve']($source, $args, $context);
            } else {
                if (is_array($source) || $source instanceof \ArrayAccess) {
                    return $source[$key] ?? null;
                } else {
                    return $source->{$key};
                }
            }
        };
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

        $fields->put($key, $field);

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

        $fields->put($singularKey.'Ids', $this->registry->field($this->registry->ID())
            ->list()
            ->nullable($field->isNullable())
            ->canSee($field->getViewPolicy())
            //->resolve([$this, 'resolveIds'])
            ->resolve(function ($model) use ($key) {
                $relation = $model->{$key};
                $relationship = $model->{$key}();

                return $relation
                    ->pluck($relationship->getRelated()->getKeyName())
                    ->toArray();
            })
        );

        $fields->put($key.'_count', $this->registry->field($this->registry->int())
            ->nullable($field->isNullable())
            ->canSee($field->getViewPolicy())
            //->resolve([$this, 'resolveCount'])
            ->resolve(function ($model) use ($key) {
                $relation = $model->{$key};

                return $relation->count();
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

        return $fields->put($key.'Id', $this->registry->field($this->registry->ID())
            ->nullable($field->isNullable())
            ->canSee($field->getViewPolicy())
            //->resolve([$this, 'resolveId'])
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

        $accessor = $relation->getPivotAccessor();
        $modelSchema = $this->registry->resolveSchemaForModel($pivot);
        $type = $field->getType()->toType()->getWrappedType(true);
        $closure = $type->config['fields'];
        $pivotField = [
            $accessor => [
                'type' => $this->registry->type($modelSchema->typename())->toType(),
                'resolve' => function ($model) use ($key, $accessor) {
                    return $model->{$accessor};
                },
            ],
        ];
        $type->config['fields'] = function () use ($closure, $pivotField) {
            return array_merge($pivotField, $closure());
        };

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
}
