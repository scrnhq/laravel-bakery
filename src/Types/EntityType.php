<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;

class EntityType extends Type
{
    protected $name;

    protected $model;

    public function __construct(string $class)
    {
        $this->name = class_basename($class);
        $this->model = app($class);
    }

    public function fields(): array
    {
        $fields = $this->model->fields();

        $relations = array_filter($this->model->relations(), function ($key) {
            return !in_array($key, $this->model->getHidden());
        }, ARRAY_FILTER_USE_KEY);

        foreach ($relations as $key => $type) {
            if ($type instanceof ListOfType) {
                $singularKey = str_singular($key);
                $fields[$singularKey . 'Ids'] = [
                    'type' => Bakery::listOf(Bakery::ID()),
                    'resolve' => function ($model) use ($key) {
                        $keyName = $model->{$key}()->getRelated()->getKeyName();
                        return $model->{$key}->pluck($keyName)->toArray();
                    },
                ];
            } else {
                $fields[$key . 'Id'] = [
                    'type' => $type instanceof NonNull ? Bakery::nonNull(Bakery::ID()) : Bakery::ID(),
                    'resolve' => function ($model) use ($key) {
                        $instance = $model->{$key};
                        return $instance ? $instance->getKey() : null;
                    },
                ];
            }
            $fields[$key] = $type;
        }

        $fields = collect($fields)->filter(function ($field, $key) {
            return !in_array($key, $this->model->getHidden());
        });

        return $fields->map(function ($field, $key) {
            if (is_array($field)) {
                if (array_key_exists('readable', $field)) {
                    return [
                        'type' => $field['type'],
                        'resolve' => function ($source, $args, $viewer) use ($key, $field) {
                            if (!$field['readable']($source, $args, $viewer)) {
                                throw new AuthorizationException('Cannot read property ' . $key . ' of ' . $this->name);
                            }
                            if (array_key_exists('resolve', $field)) {
                                return $field['resolve']($source, $args, $viewer);
                            } else {
                                return $source->getAttribute($key);
                            }
                        },
                    ];
                } elseif (array_key_exists('policy', $field)) {
                    return [
                        'type' => $field['type'],
                        'resolve' => function ($source, $args, $viewer) use ($key, $field) {
                            if (!app(Gate::class)->forUser($viewer)->check($field['policy'], $source)) {
                                throw new AuthorizationException('Cannot read property ' . $key . ' of ' . $this->name);
                            }
                            if (array_key_exists('resolve', $field)) {
                                return $field['resolve']($source, $args, $viewer);
                            } else {
                                return $source->getAttribute($key);
                            }
                        },
                    ];
                }
            }
            return $field;
        })->toArray();
    }
}
