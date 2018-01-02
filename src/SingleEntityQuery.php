<?php

namespace Bakery;

use Bakery\Queries\SingleEntityQuery as BaseSingleEntityQuery;

class SingleEntityQuery extends BaseSingleEntityQuery
{
    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
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
