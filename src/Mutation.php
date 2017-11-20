<?php

namespace Bakery;

use Bakery\Support\Field;

class Mutation extends Field
{
    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
     *
     * @return void
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }
}
