<?php

namespace Bakery\Mutations;

use Illuminate\Support\Str;
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

        return Str::camel(Str::before(class_basename($this), 'Mutation'));
    }
}
