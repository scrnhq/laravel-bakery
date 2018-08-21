<?php

namespace Bakery\Types\Definitions;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
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
     * The type resolver.
     *
     * @var callable
     */
    protected $typeResolver;

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
     * Get the definition by key.
     *
     * @param string $key
     * @return mixed
     */
    public function getDefinitionByKey(string $key)
    {
        return collect($this->definitions)->first(function ($definition) use ($key) {
            return Utils::single(resolve($definition)->getModel()) === $key;
        });
    }

    /**
     * Define the type resolver.
     *
     * @param callable $resolver
     * @return $this
     */
    public function typeResolver(callable $resolver)
    {
        $this->typeResolver = $resolver;

        return $this;
    }

    /**
     * Get the type resolver.
     *
     * @return callable
     */
    public function getTypeResolver()
    {
        return $this->typeResolver;
    }

    /**
     * Get the underlying (wrapped) type.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    public function getNamedType(): GraphQLNamedType
    {
        return Bakery::resolve($this->name);
    }

    /**
     * Returns if the underlying type is a leaf type.
     *
     * @return bool
     */
    public function isLeafType(): bool
    {
        return false;
    }
}
