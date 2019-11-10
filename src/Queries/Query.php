<?php

namespace Bakery\Queries;

use Bakery\Support\RootField;
use Illuminate\Support\Str;

abstract class Query extends RootField
{
    /**
     * Get the name of the Query, if no name is specified fall back
     * on a name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Str::camel(Str::before(class_basename($this), 'Query'));
    }
}
