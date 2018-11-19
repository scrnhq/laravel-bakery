<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\UnionType as GraphQLUnionType;

abstract class UnionType extends Type implements NamedType
{
    protected $types;

    protected $typeResolver;

    /**
     * Receives $value from resolver of the parent field and returns concrete Object BakeField for this $value.
     *
     * @param $value
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    abstract protected function resolveType($value, $context, ResolveInfo $info);

    /**
     * Get the types of the union type.
     *
     * @return array
     */
    public function types(): array
    {
        if (isset($this->types)) {
            return $this->types;
        }

        return [];
    }

    /**
     * Define the type resolver.
     *
     * @param callable $resolver
     * @return $this
     */
    public function typeResolver($resolver)
    {
        $this->typeResolver = $resolver;

        return $this;
    }

    /**
     * Get the type resolver.
     *
     * @param $value
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     * @return callable
     */
    public function getTypeResolver($value, $context, ResolveInfo $info)
    {
        if (isset($this->typeResolver)) {
            return call_user_func_array($this->typeResolver, [$value, $context, $info]);
        }

        return $this->resolveType($value, $context, $info);
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
                return $type->toNamedType();
            })->toArray(),
            'resolveType' => [$this, 'getTypeResolver'],
        ];
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return new GraphQLUnionType($this->getAttributes());
    }
}
