<?php

namespace Scrn\Bakery\Types;

use Illuminate\Database\Eloquent\Model;
use Scrn\Bakery\Support\Facades\Bakery;

class CreateInputType extends InputType
{
    /**
     * The name of the type.
     *
     * @var string 
     */
    protected $name;

    /**
     * A reference to the model.
     *
     * @var Model 
     */
    protected $model;

    /**
     * Construct a new collection filter type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = 'create' . class_basename($class) . 'Input';
        $this->model = app($class);
    }

    /**
     * Return the attributes for the filter collection type. 
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->model->fields();
    }
}
