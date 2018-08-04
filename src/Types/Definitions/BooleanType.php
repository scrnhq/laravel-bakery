<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\Type as GraphQLType;

class BooleanType extends ScalarType
{
    public function __construct()
    {
        $this->type = GraphQLType::boolean();
    }
}
