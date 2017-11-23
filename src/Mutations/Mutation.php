<?php

namespace Bakery\Mutations;

use Bakery\Support\Field;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Mutation extends Field
{
    use AuthorizesRequests;

    /**
     * Get the name of the Mutation, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }
}
