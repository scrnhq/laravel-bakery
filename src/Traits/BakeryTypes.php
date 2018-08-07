<?php

namespace Bakery\Traits;

use Bakery\Types\Definitions\Type;
use Bakery\Types\Definitions\ObjectType;
use Bakery\Types\Definitions\EloquentType;
use Bakery\Types\Definitions\ReferenceType;
use Bakery\Types\PolymorphicType;
use GraphQL\Type\Definition\Type as GraphQLType;

trait BakeryTypes
{
    public function string(): Type
    {
        return new ReferenceType(GraphQLType::string());
    }

    public function int(): Type
    {
        return new ReferenceType(GraphQLType::int());
    }

    public function ID(): Type
    {
        return new ReferenceType(GraphQLType::ID());
    }

    public function boolean(): Type
    {
        return new ReferenceType(GraphQLType::boolean());
    }

    public function float()
    {
        return new ReferenceType(GraphQLType::float());
    }

    public function model(string $definition): Type
    {
        return new EloquentType($definition);
    }

    public function collection($type): Type
    {
        return $this->model($type)->list();
    }

    public function field($type): ObjectType
    {
        return new ObjectType($type);
    }

    public function list($type): Type
    {
        return $this->type($type)->list();
    }

    public function polymorhpic(array $definitions): PolymorphicType
    {
        return new PolymorphicType($definitions);
    }
}
