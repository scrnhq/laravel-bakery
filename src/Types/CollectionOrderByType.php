<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\Type;
use Bakery\Concerns\ModelSchemaAware;
use Bakery\Types\Definitions\InputType;
use Bakery\Types\Definitions\EloquentType;

class CollectionOrderByType extends InputType
{
    use ModelSchemaAware;

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
        $fields = collect();

        foreach ($this->schema->getFields() as $name => $field) {
            $fields->put($name, Bakery::type('Order')->nullable());
        }

        $this->schema->getRelationFields()->filter(function (Type $field) {
            return $field instanceof EloquentType;
        })->each(function (EloquentType $field, $relation) use ($fields) {
            $fields->put($relation, Bakery::type($field->name().'OrderBy')->nullable());
        });

        return $fields->toArray();
    }
}
