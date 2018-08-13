<?php

namespace Bakery\Types\Definitions;

class BaseType extends Type
{
    /**
     * BaseType constructor.
     * @param \GraphQL\Type\Definition\ScalarType $type
     */
    public function __construct(\GraphQL\Type\Definition\ScalarType $type)
    {
        parent::__construct($type);
    }
}
