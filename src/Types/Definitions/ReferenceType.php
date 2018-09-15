<?php

namespace Bakery\Types\Definitions;

use Bakery\Support\Facades\Bakery;

class ReferenceType extends Type
{
    /**
     * The reference of the type.
     *
     * @var
     */
    protected $reference;

    /**
     * ReferenceType constructor.
     * @param $reference
     */
    public function __construct(string $reference)
    {
        parent::__construct(null);

        $this->reference = $reference;
    }

    /**
     * Resolve the type that is being referenced.
     *
     * @return \GraphQL\Type\Definition\NamedType
     */
    public function type(): \GraphQL\Type\Definition\NamedType
    {
        return Bakery::resolve($this->reference);
    }
}
