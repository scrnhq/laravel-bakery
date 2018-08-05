<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\InputType;

class CollectionRootSearchType extends InputType
{
    use ModelAware;

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
