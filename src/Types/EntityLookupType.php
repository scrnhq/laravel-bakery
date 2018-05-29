<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;

class EntityLookupType extends ModelAwareInputType
{
    /**
     * Get the name of the Entity Lookup Type.
     *
     * @return string
     */
    protected function name(): string
    {
        return Utils::typename($this->model->getModel()) . 'LookupType';
    }

    /**
     * Define the fields for the entity lookup type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->model->getLookupFields();
    }
}
