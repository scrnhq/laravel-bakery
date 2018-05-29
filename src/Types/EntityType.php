<?php

namespace Bakery\Types;

use Closure;
use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class EntityType extends ModelAwareType
{
    /**
     * Get the name of the Entity type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel());
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
                $this->checkPolicy($field, $source, $args, $viewer);
            }

            if (array_key_exists('resolve', $field)) {
                return $field['resolve']($source, $args, $viewer);
            } else {
                return $source->getAttribute($key);
            }
        };
    }

    /**
     * Check the policy of a field.
     *
     * @param array $field
     * @return void
     */
    protected function checkPolicy(array $field, $source, $args, $viewer)
    {
        $policy = $field['policy'];

        // Check if the policy method is callable
        if (is_callable($policy)) {
            if (! $policy($source, $args, $viewer)) {
                throw new AuthorizationException(
                    'Cannot read property '.$key.' of '.$this->name
                );
            }
        }

        // Check if there is a policy with this name
        if (is_string($policy)) {
            $gate = app(Gate::class)->forUser($viewer);
            if (! $gate->check($policy, $source)) {
                throw new AuthorizationException('Cannot read property '.$key.' of '.$this->name);
            }
        }
    }

    public function fields(): array
    {
        $fields = $this->model->getFields();
        $relations = $this->model->getRelations();

        foreach ($relations as $key => $type) {
            if ($type instanceof ListOfType) {
                $singularKey = str_singular($key);
                $fields[$singularKey.'Ids'] = [
                    'type' => Bakery::listOf(Bakery::ID()),
                    'resolve' => function ($model) use ($key) {
                        $keyName = $model->getModel()->{$key}()->getRelated()->getKeyName();

                        return $model->{$key}->pluck($keyName)->toArray();
                    },
                ];
            } else {
                $fields[$key.'Id'] = [
                    'type' => $type instanceof NonNull ? Bakery::nonNull(Bakery::ID()) : Bakery::ID(),
                    'resolve' => function ($model) use ($key) {
                        $instance = $model->{$key};

                        return $instance ? $instance->getKey() : null;
                    },
                ];
            }
            $fields[$key] = $type;
        }

        return collect($fields)->map(function ($field, $key) {
            $field = Utils::toFieldArray($field);
            $field['resolve'] = $this->createFieldResolver($field, $key);

            return $field;
        })->toArray();
    }
}
