<?php

namespace Scrn\Bakery;

use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Fluent;

class EntityType extends Fluent
{
    protected $model;

    public function attributes()
    {
        return [
            'name' => class_basename(app($this->model)),
        ];
    }

    public function fields()
    {
        return app($this->model)->fields();
    }

    public function __construct(string $model, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->model = $model;
    }

    public function getFields()
    {
        $fields = $this->fields();
        return $fields;
    }

    public function getAttributes()
    {
        $attributes = $this->attributes();

        return array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            },
        ], $attributes);
    }

    public function toArray()
    {
        return $this->getAttributes();
    }

    public function toType()
    {
        return new ObjectType($this->toArray());
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key] : null;
    }
}
