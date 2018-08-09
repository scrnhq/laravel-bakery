<?php

namespace Bakery\Types;

use Closure;
use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;
use Bakery\Types\Definitions\ObjectType;
use Bakery\Types\Definitions\EloquentType;
use Illuminate\Database\Eloquent\Relations;
use Bakery\Types\Definitions\PolymorphicType;

class EntityType extends ObjectType
{
    use ModelAware;

    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename();
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
        return function ($source, $args, $viewer) use ($key, $field) {
            if (array_key_exists('policy', $field)) {
                $this->checkPolicy($field, $key, $source, $args, $viewer);
            }

            if (array_key_exists('resolve', $field)) {
                return $field['resolve']($source, $args, $viewer);
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
        $fields = $this->schema->getFields();
        $relationFields = $this->getRelationFields();

        return collect($fields)
            ->merge($relationFields)
            ->toArray();
    }

    /**
     * Get the relation fields of the entity.
     *
     * @return Collection
     */
    protected function getRelationFields(): Collection
    {
        $fields = collect();

        $relations = $this->schema->getRelationFields();

        foreach ($relations as $key => $field) {
            if ($field instanceof EloquentType) {
                $fields = $fields->merge($this->getFieldsForRelation($key, $field));
            } elseif ($field instanceof PolymorphicType) {
                $fields = $fields->merge($this->getFieldsForPolymorphicRelation($key, $field));
            }
        }

        return $fields;
    }

    /**
     * Get the fields for a relation.
     *
     * @param string $key
     * @param EloquentType $field
     * @return Collection
     */
    protected function getFieldsForRelation(string $key, EloquentType $field): Collection
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
     * @param EloquentType $field
     * @return Collection
     */
    protected function getPluralRelationFields(string $key, EloquentType $field): Collection
    {
        $fields = collect();
        $singularKey = str_singular($key);

        $fields->put($singularKey.'Ids', Bakery::ID()->list()->resolve(function ($model) use ($key) {
            $relation = $model->{$key};
            $relationship = $model->{$key}();

            return $relation
                ->pluck($relationship->getRelated()->getKeyName())
                ->toArray();
        }));

        $fields->put($key.'_count', Bakery::int()->resolve(function ($model) use ($key) {
            $relation = $model->{$key};

            return $relation->count();
        }));

        return $fields;
    }

    /**
     * Get the fields for a singular relation.
     *
     * @param string $key
     * @param EloquentType $field
     * @return Collection
     */
    protected function getSingularRelationFields(string $key, EloquentType $field): Collection
    {
        $fields = collect();

        return $fields->put($key.'Id', Bakery::ID()->resolve(function ($model) use ($key) {
            $relation = $model->{$key};

            return $relation ? $relation->getKey() : null;
        }));
    }

    /**
     * Get the fields for a belongs to many relation.
     *
     * @param string $key
     * @param EloquentType $field
     * @param Relations\BelongsToMany $relation
     * @return Collection
     */
    protected function getBelongsToManyRelationFields(string $key, EloquentType $field, Relations\BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();

        if (! Bakery::hasModelSchema($pivot)) {
            return $fields;
        }

        $accessor = $relation->getPivotAccessor();
        $definition = resolve(Bakery::getModelSchema($pivot));
        $type = $field->getNamedType();
        $closure = $type->config['fields'];
        $pivotField = [
            $accessor => [
                'type' => Bakery::type($definition->typename())->toType(),
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
     * @param \Bakery\Types\Definitions\PolymorphicType $field
     * @return Collection
     */
    public function getFieldsForPolymorphicRelation(string $key, PolymorphicType $field): Collection
    {
        return collect([$key => $field->setName(Utils::typename($key))]);
    }
}
