<?php

namespace Bakery\Types\Definitions;

use Bakery\Support\TypeRegistry;
use GraphQL\Type\Definition\ScalarType;

class InternalType extends RootType
{
    /**
     * BaseType constructor.
     *
     * @param  \Bakery\Support\TypeRegistry  $registry
     * @param  \GraphQL\Type\Definition\ScalarType  $type
     */
    public function __construct(TypeRegistry $registry, ScalarType $type)
    {
        parent::__construct($registry, $type);
    }
}
