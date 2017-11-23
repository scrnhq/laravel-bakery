<?php

namespace Bakery\Queries;

use Bakery\Support\Field;

class Query extends Field
{
    /**
     * Get the name of the Query, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    protected function name(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return camel_case(str_before(class_basename($this), 'Query'));
    }
}
