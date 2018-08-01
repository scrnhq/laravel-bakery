<?php

namespace Bakery\Types;

use Closure;
use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Type as BaseType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Auth\Access\AuthorizationException;

class EntityType extends BaseType
{
    use ModelAware;

    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    protected function name(): string
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
     * Check the policy of a field.
     *
     * @param array $field
     * @param string $key
     * @param $source
     * @param $args
     * @param $viewer
     * @return void
     * @throws AuthorizationException
     */
    protected function checkPolicy(array $field, string $key, $source, $args, $viewer)
    {
        $policy = $field['policy'];
        $gate = app(Gate::class)->forUser($viewer);

        // Check if the policy method is callable
        if (is_callable($policy) && ! $policy($source, $args, $viewer)) {
            throw new AuthorizationException(
                'Cannot read property '.$key.' of '.$this->name
            );
        }

        // Check if there is a policy with this name
        if (is_string($policy) && ! $gate->check($policy, $source)) {
            throw new AuthorizationException('Cannot read property '.$key.' of '.$this->name);
        }
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
            ->map(function ($field, $key) {
                $field = Utils::toFieldArray($field);
                $field['resolve'] = $this->createFieldResolver($field, $key);

                return $field;
            })->toArray();
    }

    /**
     * Get the relation fields of the entity.
     *
     * @return array
     */
    protected function getRelationFields(): array
    {
        $fields = collect();
        $relations = $this->schema->getRelationFields();

        foreach ($relations as $key => $field) {
            $relationship = $this->model->{$key}();
            $fieldType = Utils::nullifyField($field)['type'];

            $fields->put($key, $field);

            if ($fieldType instanceof ListOfType) {
                $fields = $fields->merge($this->getPluralRelationFields($key, $field));
            } else {
                $fields = $fields->merge($this->getSingularRelationFields($key, $field));
            }

            if ($relationship instanceof Relations\BelongsToMany) {
                $fields = $fields->merge(
                    $this->getBelongsToManyRelationFields($key, $field, $relationship)
                );
            }
        }

        return $fields->toArray();
    }

    /**
     * Get the fields for a plural relation.
     *
     * @param string $key
     * @return Collection
     */
    protected function getPluralRelationFields(string $key): Collection
    {
        $fields = collect();
        $singularKey = str_singular($key);

        $fields->put($singularKey.'Ids', [
            'type' => Type::listOf(Type::ID()),
            'resolve' => function ($model) use ($key) {
                $relation = $model->{$key};
                $relationship = $model->{$key}();

                return $relation
                    ->pluck($relationship->getRelated()->getKeyName())
                    ->toArray();
            },
        ]);

        $fields->put($key.'_count', [
            'type' => Type::nonNull(Type::int()),
            'resolve' => function ($model) use ($key) {
                $relation = $model->{$key};

                return $relation->count();
            },
        ]);

        return $fields;
    }

    /**
     * Get the fields for a singular relation.
     *
     * @param string $key
     * @param array $field
     * @return Collection
     */
    protected function getSingularRelationFields(string $key, array $field): Collection
    {
        $fields = collect();

        return $fields->put($key.'Id', [
            'type' => $field['type'] instanceof NonNull
                ? Type::nonNull(Type::ID())
                : Type::ID(),
            'resolve' => function ($model) use ($key) {
                $relation = $model->{$key};

                return $relation ? $relation->getKey() : null;
            },
        ]);
    }

    /**
     * Get the fields for a belongs to many relation.
     *
     * @param string $key
     * @param array $field
     * @param Relations\BelongsToMany $relation
     * @return Collection
     */
    protected function getBelongsToManyRelationFields(string $key, array $field, Relations\BelongsToMany $relation): Collection
    {
        $fields = collect();
        $pivot = $relation->getPivotClass();

        if (! Bakery::hasModelSchema($pivot)) {
            return $fields->put($key, $field);
        }

        $type = $field['type']->getWrappedType(true);
        $accessor = $relation->getPivotAccessor();
        $definition = resolve(Bakery::getModelSchema($pivot));
        $closure = $type->config['fields'];
        $pivotField = [
            $accessor => [
                'type' => Bakery::type($definition->typename()),
                'resolve' => function ($model) use ($key, $accessor) {
                    return $model->{$accessor};
                },
            ],
        ];
        $type->config['fields'] = function () use ($closure, $pivotField) {
            return array_merge($pivotField, $closure());
        };

        return $fields->put($key, $field);
    }
}
