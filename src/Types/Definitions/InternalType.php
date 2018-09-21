<?php

namespace Bakery\Types\Definitions;

use Bakery\TypeRegistry;
use GraphQL\Type\Definition\ScalarType;

class InternalType extends Type
{
    /**
     * BaseType constructor.
     *
     * @param \Bakery\TypeRegistry $registry
     * @param \GraphQL\Type\Definition\ScalarType $type
     */
    public function __construct(TypeRegistry $registry, ScalarType $type)
    {
        parent::__construct($registry, $type);
    }
}
