<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Concerns\ModelSchemaAware;
use Bakery\Types\Definitions\InputType;

class CollectionRootSearchType extends InputType
{
    use ModelSchemaAware;

    /**
     * Get the name of the Collection Root Search Type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->schema->typename().'RootSearch';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'query' => Bakery::string(),
            'fields' => Bakery::type($this->schema->typename().'Search'),
        ];
    }
}
