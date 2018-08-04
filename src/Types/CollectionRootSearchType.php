<?php

namespace Bakery\Types;

use Bakery\Concerns\ModelAware;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;

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
            'fields' => Bakery::resolve($this->schema->typename().'Search'),
        ];
    }
}
