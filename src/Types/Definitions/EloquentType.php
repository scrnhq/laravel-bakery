<?php

namespace Bakery\Types\Definitions;

use Bakery\Utils\Utils;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class EloquentType extends Type
{
    /**
     * The underlying definition.
     *
     * @var mixed
     */
    protected $definition;

    /**
     * Construct a new Eloquent type.
     *
     * @param string $definition
     */
    public function __construct(string $definition = null)
    {
        parent::__construct();

        if (isset($definition)) {
            $this->definition = $definition;
        }

        Utils::invariant($this->definition, 'No definition defined on "'.get_class($this).'"');

        $this->definition = resolve($this->definition);

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
        if (isset($this->name)) {
            return $this->name;
        }

        return $this->definition->typename();
    }

    /**
     * Return the underlying, named type.
     *
     * @return GraphQLNamedType
     */
    public function getNamedType(): GraphQLNamedType
    {
        return Bakery::type($this->definition->typename())->getNamedType();
    }
}
