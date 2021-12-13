<?php

namespace Bakery\Types\Definitions;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Type as GraphQLType;

abstract class ScalarType extends RootType implements NamedType
{
    /**
     * Serializes an internal value to include in a response.
     *
     * @param  string  $value
     * @return string
     */
    abstract public function serialize($value);

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     * @param  mixed  $value
     * @return mixed
     */
    abstract public function parseValue($value);

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * E.g.
     * {
     *   user(email: "user@example.com")
     * }
     *
     * @param  \GraphQL\Language\AST\Node  $valueNode
     * @return string
     *
     * @throws Error
     */
    abstract public function parseLiteral($valueNode);

    /**
     * Convert the type to a GraphQL BakeField.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return new CustomScalarType([
            'name' => $this->name(),
            'serialize' => [$this, 'serialize'],
            'parseValue' => [$this, 'parseValue'],
            'parseLiteral' => [$this, 'parseLiteral'],
        ]);
    }
}
