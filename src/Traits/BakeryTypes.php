<?php

namespace Bakery\Traits;

use Bakery\Types\Definitions\Type;
use Bakery\Types\Definitions\IDType;
use Bakery\Types\Definitions\IntType;
use Bakery\Types\Definitions\ObjectType;
use Bakery\Types\Definitions\ScalarType;
use Bakery\Types\Definitions\StringType;
use Bakery\Types\Definitions\BooleanType;
use Bakery\Types\Definitions\EloquentType;

trait BakeryTypes
{
    public function string(): Type
    {
        return new StringType();
    }

    public function int(): Type
    {
        return new IntType();
    }

    public function ID(): Type
    {
        return new IDType();
    }

    public function boolean(): Type
    {
        return new BooleanType();
    }

    // public function float()
    // {
    //     return Type::float();
    // }

    public function model(string $definition): Type
    {
        return new EloquentType($definition);
    }

    public function collection($type): Type
    {
        if ($type instanceof ScalarType) {
            return $definition->list();
        }

        return $this->model($type)->list();
    }

    public function field($type): ObjectType
    {
        return new ObjectType($type);
    }

    public function list($type): Type
    {
        $this->collection($type);
    }
}
