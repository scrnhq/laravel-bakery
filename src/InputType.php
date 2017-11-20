<?php

namespace Bakery;

use Bakery\Types\InputType as BaseInputType;

class InputType extends BaseInputType
{
    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
     *
     * @return void
     */
    protected function name()
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return studly_case(str_before(class_basename($this), 'Type'));
    }
}
