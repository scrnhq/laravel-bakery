<?php

namespace Scrn\Bakery\Types;

use Scrn\Bakery\Support\Facades\Bakery;

class EntityCollectionType extends Type
{
    protected $model;

    public function fields()
    {
        return [
            'pagination' => Bakery::getType('Pagination'), 
            'items' => Bakery::listOf(Bakery::getType(class_basename($this->model))),
        ];
    }

    public function __construct(string $class, array $attributes = [])
    {
        $name = class_basename($class) . 'Collection';
        $this->model = app($class);

        parent::__construct(array_merge([
            'name' => $name,
        ], $attributes));
    }
}
