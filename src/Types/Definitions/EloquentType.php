<?php

namespace Bakery\Types\Definitions;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use GraphQL\Type\Definition\Type as GraphQlType;

class EloquentType extends Type
{
    /**
     * Construct a new Eloquent type.
     *
     * @param string $definition
     */
    public function __construct(string $definition)
    {
        $this->definition = resolve($definition);

        Utils::invariant(
            Utils::usesTrait($this->definition, Introspectable::class),
            get_class($this->definition).' does not have the '.Introspectable::class.' trait'
        );
    }

    /**
     * The name of the type.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->definition->typename();
    }

    /**
     * Return the underlying, wrapped type.
     *
     * @return GraphQLType
     */
    public function getWrappedType(): GraphQLType
    {
        return Bakery::resolve($this->name())->getType();
    }
}
