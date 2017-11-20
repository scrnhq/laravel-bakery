<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;

class EntityLookupType extends InputType
{
    /**
     * The name of the entity lookup type.
     *
     * @var string
     */
    protected $name;

    /**
     * The model of the entity lookup type.
     *
     * @var Model
     */
    protected $model;

    /**
     * Construct a new entity lookup type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = class_basename($class) . 'LookupType';
        $this->model = app($class);
    }

    /**
     * Define the fields for the entity lookup type.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_merge(
            [$this->model->getKeyName() => Bakery::ID()],
            $this->model->lookupFields()
        );
    }
}
