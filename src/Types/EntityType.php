<?php

namespace Bakery\Types;

use Closure;
use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
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
        $fields = [];
        $relations = $this->schema->getRelations();

        foreach ($relations as $key => $field) {
            $relationship = $this->model->{$key}();
            $fieldType = Utils::nullifyField($field)['type'];

            if ($fieldType instanceof ListOfType) {
                $singularKey = str_singular($key);
                $fields[$singularKey.'Ids'] = [
                    'type' => Type::listOf(Type::ID()),
                    'resolve' => function ($model) use ($key) {
                        $relation = $model->{$key};
                        $relationship = $model->{$key}();

                        return $relation
                            ->pluck($relationship->getRelated()->getKeyName())
                            ->toArray();
                    },
                ];
                $fields[$key.'_count'] = [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => function ($model) use ($key) {
                        $relation = $model->{$key};
                        return $relation->count();
                    },
                ];
            } else {
                $fields[$key.'Id'] = [
                    'type' => $field instanceof NonNull ? Type::nonNull(Type::ID()) : Type::ID(),
                    'resolve' => function ($model) use ($key) {
                        $relation = $model->{$key};
                        return $relation ? $relation->getKey() : null;
                    },
                ];
            }

            if ($relationship instanceof Relations\BelongsToMany) {
                $pivot = $relationship->getPivotClass();

                if (Bakery::hasModelSchema($pivot)) {
                    $type = $field['type']->getWrappedType(true);
                    $definition = resolve(Bakery::getModelSchema($pivot));
                    $closure = $type->config['fields'];
                    $pivotField = [
                        'pivot' => [
                            'type' => Bakery::type($definition->typename()),
                            'resolve' => function ($model) use ($key) {
                                return $model->pivot;
                            }
                        ],
                    ];
                    $type->config['fields'] = function () use ($closure, $pivotField) {
                        return array_merge($pivotField, $closure());
                    };
                    $fields[$key] = Utils::swapWrappedType($field, $type); 
                } else {
                    $fields[$key] = $field;
                }
                $fields[$key] = $field;
            } else {
                $fields[$key] = $field;
            }
        }

        return $fields;
    }
}
