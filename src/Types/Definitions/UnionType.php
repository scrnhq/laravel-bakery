<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\UnionType as GraphQLUnionType;

abstract class UnionType extends Type
{
    protected $types;

    /**
     * Receives $value from resolver of the parent field and returns concrete Object Type for this $value.
     *
     * @param $value
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract public function resolveType($value, $context, ResolveInfo $info);

    /**
     * Get the types of the union type.
     *
     * @return array
     */
    public function types(): array
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
            'types' => collect($this->types())->map(function (Type $type) {
                return $type->toType();
            })->toArray(),
            'resolveType' => function ($value, $context, ResolveInfo $info) {
                return $this->resolveType($value, $context, $info);
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
        return $this->type = new GraphQLUnionType($this->getAttributes());
    }
}
