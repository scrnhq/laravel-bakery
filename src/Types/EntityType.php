<?php

namespace Scrn\Bakery\Types;

class EntityType extends Type
{
    protected $model;

    public function fields()
    {
        return $this->model->fields();
    }

    public function __construct(string $class, array $attributes = [])
    {
        $name = class_basename($class);
        $this->model = app($class);

        parent::__construct(array_merge([
            'name' => $name,
        ], $attributes));
    }
}
