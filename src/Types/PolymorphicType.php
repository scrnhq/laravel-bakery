<?php

namespace Bakery\Types;

use Bakery\Types\Definitions\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\Type as GraphQLType;

abstract class PolymorphicType extends Type
{
    protected $types;

    abstract public function resolveType($value);

    public function types()
    {
        return $this->types;
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name(),
            'types' => $this->types(),
            'resolveType' => function ($value) {
                return $this->resolveType($value);
            },
        ];
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return $this->type = new UnionType($this->getAttributes());
    }
}
