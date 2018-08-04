<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;

abstract class CustomEntityType extends EntityType
{
    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return Utils::typename(str_before(class_basename($this), 'Type'));
    }
}
