<?php

namespace Bakery\Types;

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
        return array_merge(
            $this->model->fields(),
            $this->model->relations()
        );
    }
}
