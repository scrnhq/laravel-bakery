<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;

class CollectionSearchType extends InputType
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
        $this->name = class_basename($class) . 'Search';
        $this->model = app($class);
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->model->fields() as $name => $type) {
            if (is_array($type)) {
                $type = Type::getNamedType($type['type']);
            } else {
                $type = Type::getNamedType($type);
            }
            if ($type instanceof StringType) {
                $fields[$name] = Bakery::boolean();
            }
        }

        foreach ($this->model->relations() as $relation => $type) {
            if (is_array($type)) {
                $type = Type::getNamedType($type['type']);
            } else {
                $type = Type::getNamedType($type);
            }
            $fields[$relation] = Bakery::type($type->name . 'Search');
        }

        return $fields;
    }
}
