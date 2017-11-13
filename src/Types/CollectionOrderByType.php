<?php

namespace Scrn\Bakery\Types;

use Scrn\Bakery\Types\EnumType;
use Illuminate\Database\Eloquent\Model;
use Scrn\Bakery\Support\Facades\Bakery;

class CollectionOrderByType extends EnumType
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
     * Construct a new collection orderby type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = class_basename($class) . 'OrderBy';
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
    protected function values(): array
    {
        $values = [];

        foreach($this->model->fields() as $name => $type) {
            $values[] = $name . '_ASC';
            $values[] = $name . '_DESC';
        }

        return $values; 
    }
}
