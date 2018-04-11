<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;

class CollectionRootSearchType extends InputType
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
        $this->name = class_basename($class) . 'RootSearch';
        $this->model = app($class);
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'query' => Bakery::nonNull(Bakery::string()),
            'fields' => Bakery::nonNull(Bakery::type(class_basename($this->model) . 'Search')),
        ];
    }
}
