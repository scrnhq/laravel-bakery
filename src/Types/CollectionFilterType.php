<?php

namespace Scrn\Bakery\Types;

use Illuminate\Database\Eloquent\Model;
use Scrn\Bakery\Support\Facades\Bakery;

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
        $fields = $this->model->fields();

        foreach ($fields as $name => $type) {
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
        }

        $fields['AND'] = Bakery::listOf(Bakery::getType($this->name));
        $fields['OR'] = Bakery::listOf(Bakery::getType($this->name));

        return $fields;
    }
}
