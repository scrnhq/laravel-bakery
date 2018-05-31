<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Concerns\ModelAware;

class EntityLookupType extends InputType
{
    use ModelAware;

    /**
     * Get the name of the Entity Lookup Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return $this->schema->typename().'LookupType';
    }

    /**
     * Define the fields for the entity lookup type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->schema->getLookupFields();
    }
}
