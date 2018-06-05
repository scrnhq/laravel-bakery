<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model;

class CollectionOrderByType extends InputType
{
    use ModelAware;

    /**
     * Define the collection order type as an input type.
     *
     * @var boolean
     */
    protected $input = true;

    /**
     * Get the name of the Collection Order By Type.
     *
     * @return string
     */
    protected function name(): string
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
            $fields[$name] = Bakery::getType('Order');
        }

        foreach ($this->schema->getRelations() as $relation => $field) {
            $type = Type::getNamedType($field['type']);
            $fields[$relation] = Bakery::getType($type->name . 'OrderBy');
        }

        return $fields;
    }
}
