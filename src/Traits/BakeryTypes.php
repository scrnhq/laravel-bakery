<?php

namespace Scrn\Bakery\Traits;

use GraphQL\Type\Definition\Type;

trait BakeryTypes
{
    public function ID()
    {
        return Type::ID();
    }

    public function string()
    {
        return Type::string();
    }

    public function int()
    {
        return Type::int();
    }

    public function boolean()
    {
        return Type::boolean();
    }

    public function float()
    {
        return Type::float();
    }

    public function listOf($wrappedType)
    {
        return Type::listOf($wrappedType);
    }

    public function nonNull($wrappedType)
    {
        return Type::nonNull($wrappedType);
    }
}
