<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

class CollectionSearchType extends InputType
{
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
        $fields = [
            'query' => Bakery::nonNull(Bakery::string()),
            'fields' => Bakery::listOf(Bakery::string()),
        ];

        return $fields;
    }
}
