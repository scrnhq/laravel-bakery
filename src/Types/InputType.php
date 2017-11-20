<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as BaseType;

class InputType extends Type
{
    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @return ObjectType
     */
    public function toGraphQLType(): BaseType
    {
        return new InputObjectType($this->toArray());
    }
}
