<?php

namespace Bakery\Types;

use Bakery\Types\Definitions\EloquentInputType;

class EntityLookupType extends EloquentInputType
{
    /**
     * Get the name of the Entity Lookup BakeField.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->modelSchema->typename().'LookupType';
    }

    /**
     * Define the fields for the entity lookup type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->modelSchema->getLookupFields()->toArray();
    }
}
