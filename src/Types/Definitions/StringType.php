<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\Type as GraphQLType;

class StringType extends ScalarType
{
    public function __construct()
    {
        $this->type = GraphQLType::string();
    }
}