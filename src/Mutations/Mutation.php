<?php

namespace Bakery\Mutations;

use Bakery\Support\RootField;

abstract class Mutation extends RootField
{
    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }
}
