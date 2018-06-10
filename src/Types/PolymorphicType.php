<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\Type as BaseType;

class PolymorphicType extends Type
{
    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name,
            'types' => $this->types,
            'resolveType' => function ($value) {
                return $this->resolveType($value);
            },
        ];
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @param array $options
     * @return BaseType
     */
    public function toGraphQLType(array $options = []): BaseType
    {
        return $this->type = new UnionType(array_merge($this->toArray(), $options));
    }
}
