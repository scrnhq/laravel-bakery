<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\EloquentInputType;

class CollectionRootSearchType extends EloquentInputType
{
    /**
     * Get the name of the Collection Root Search BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'RootSearch';
    }

    /**
     * Return the fields for the collection filter type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'query' => $this->registry->field($this->registry->string()),
            'fields' => $this->registry->field($this->modelSchema->typename().'Search'),
        ];
    }
}
