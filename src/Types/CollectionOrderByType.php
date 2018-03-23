<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;

class CollectionOrderByType extends InputType
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
     * Define the collection order type as an input type.
     *
     * @var boolean
     */
    protected $input = true;

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
     * Return the fields for the collection order by type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->model->fields() as $name => $type) {
            $fields[$name] = Bakery::getType('Order');
        }

        foreach ($this->model->relations() as $relation => $type) {
            if (is_array($type)) {
                $type = Type::getNamedType($type['type']);
            } else {
                $type = Type::getNamedType($type);
            }
            $type = Type::getNamedType($type);
            $fields[$relation] = Bakery::getType($type->name . 'OrderBy');
        }

        return $fields;
    }
}
