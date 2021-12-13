<?php

namespace Bakery\Types\Definitions;

use Bakery\Support\TypeRegistry;
use GraphQL\Type\Definition\NamedType as GraphQLNamedType;

class ReferenceType extends RootType
{
    /**
     * Reference to the type.
     *
     * @var string
     */
    protected $reference;

    /**
     * BaseType constructor.
     *
     * @param  \Bakery\Support\TypeRegistry  $registry
     * @param  string  $reference
     */
    public function __construct(TypeRegistry $registry, string $reference)
    {
        parent::__construct($registry);

        $this->reference = $reference;
    }

    /**
     * @return \GraphQL\Type\Definition\NamedType
     *
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function getType(): GraphQLNamedType
    {
        if (isset($this->type)) {
            return $this->type;
        }

        $this->type = $this->getRegistry()->resolve($this->reference);

        return $this->type;
    }
}
