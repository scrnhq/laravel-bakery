<?php

namespace Scrn\Bakery\Types;

class EntityType extends Type
{
    protected $name;

    protected $model;

    public function __construct(string $class)
    {
        $this->name = class_basename($class);
        $this->model = app($class);
    }

    public function attributes(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function fields(): array
    {
        return $this->model->fields();
    }
}
