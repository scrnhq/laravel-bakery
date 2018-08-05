<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\InputType;

class CollectionOrderByType extends InputType
{
    use ModelAware;

    /**
     * Define the collection order type as an input type.
     *
     * @var bool
     */
    protected $input = true;

    /**
     * Get the name of the Collection Order By Type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename().'OrderBy';
    }

    /**
     * Return the fields for the collection order by type.
     *
     * @return array
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->schema->getFields() as $name => $field) {
            $fields[$name] = Bakery::type('Order')->nullable();
        }

        foreach ($this->schema->getRelationFields() as $relation => $field) {
            $fields[$relation] = Bakery::type($field->name().'OrderBy')->nullable();
        }

        return $fields;
    }
}
