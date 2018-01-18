<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNullType;

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
        $fields = array_merge(
            $this->model->fields(),
            $this->model->relations()
        );

        foreach ($this->model->relations() as $key => $type) {
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
                    'type' => $type instanceof NonNullType ? Bakery::nonNull(Bakery::ID()) : Bakery::ID(),
                    'resolve' => function ($model) use ($key) {
                        $instance = $model->{$key};
                        return $instance ? $instance->getKey() : null;
                    }
                ];
            }
        }

        return array_filter($fields, function ($key) {
            return !in_array($key, $this->model->getHidden());
        }, ARRAY_FILTER_USE_KEY);
    }
}
