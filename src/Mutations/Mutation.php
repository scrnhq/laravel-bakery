<?php

namespace Bakery\Mutations;

use Bakery\Support\Field;

abstract class Mutation extends Field
{
    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }
}
