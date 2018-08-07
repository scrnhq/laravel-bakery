<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\ReferenceType;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class PolymorphicType extends ReferenceType
{
    /**
     * The definitions of a polymorphic type.
     *
     * @var array
     */
    protected $definitions;

    /**
     * PolymorphicType constructor.
     *
     * @param array $definitions
     */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    /**
     * Get the definitions of a polymorphic type.
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Get the underlying (wrapped) type.
     *
     * @return GraphQLNamedType
     */
    public function getNamedType(): GraphQLNamedType
    {
        return Bakery::resolve($this->name);
    }
}
