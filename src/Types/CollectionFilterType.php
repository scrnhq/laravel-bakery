<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ListOfType;

class CollectionFilterType extends InputType
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
     * Define the collection filter type as an input type.
     *
     * @var boolean
     */
    protected $input = true;

    /**
     * Construct a new collection filter type.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->name = class_basename($class) . 'Filter';
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
            $fields = array_merge($fields, $this->getFilters($name, $type));
        }

        foreach ($this->model->relations() as $relation => $type) {
            $type = Type::getNamedType($type);
            $fields[$relation] = Bakery::getType($type->name . 'Filter');
        }

        $fields['AND'] = Bakery::listOf(Bakery::getType($this->name));
        $fields['OR'] = Bakery::listOf(Bakery::getType($this->name));

        return $fields;
    }

    /**
     * Return the filters for a field.
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    public function getFilters(string $name, $type): array
    {
        $type = Type::getNamedType($type);
        $fields = [];

        $fields[$name] = $type;
        $fields[$name . '_contains'] = $type;
        $fields[$name . '_not_contains'] = $type;
        $fields[$name . '_starts_with'] = $type;
        $fields[$name . '_not_starts_with'] = $type;
        $fields[$name . '_ends_with'] = $type;
        $fields[$name . '_not_ends_with'] = $type;
        $fields[$name . '_not'] = $type;
        $fields[$name . '_not_in'] = Bakery::listOf($type);
        $fields[$name . '_in'] = Bakery::listOf($type);
        $fields[$name . '_lt'] = $type;
        $fields[$name . '_lte'] = $type;
        $fields[$name . '_gt'] = $type;
        $fields[$name . '_gte'] = $type;

        return $fields;
    }
}
