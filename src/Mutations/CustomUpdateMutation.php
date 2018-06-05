<?php

namespace Bakery\Mutations;

abstract class CustomUpdateMutation extends EntityMutation
{
    use Concerns\QueriesModel;

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

        return camel_case(str_before(class_basename($this), 'Mutation'));
    }
}
