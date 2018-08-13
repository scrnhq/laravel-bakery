<?php

namespace Bakery\Traits;

use Bakery\Types\Definitions\Type;
use Bakery\Types\Definitions\BaseType;
use Bakery\Types\Definitions\ObjectType;
use Bakery\Types\Definitions\EloquentType;
use Bakery\Types\Definitions\ReferenceType;
use Bakery\Types\Definitions\PolymorphicType;
use GraphQL\Type\Definition\Type as GraphQLType;

trait BakeryTypes
{
    public function string(): BaseType
    {
        return new BaseType(GraphQLType::string());
    }

    public function int(): BaseType
    {
        return new BaseType(GraphQLType::int());
    }

    public function ID(): BaseType
    {
        return new BaseType(GraphQLType::ID());
    }

    public function boolean(): BaseType
    {
        return new BaseType(GraphQLType::boolean());
    }

    public function float(): BaseType
    {
        return new BaseType(GraphQLType::float());
    }

    public function model(string $definition): EloquentType
    {
        return new EloquentType($definition);
    }

    public function collection($type): EloquentType
    {
        return $this->model($type)->list();
    }

    public function field($type): ObjectType
    {
        return new ObjectType($type);
    }

    public function list($type): ReferenceType
    {
        return $this->type($type)->list();
    }

    public function polymorphic(array $definitions): PolymorphicType
    {
        return new PolymorphicType($definitions);
    }

    /**
     * Get a reference to a registered type.
     *
     * @api
     * @param string $name
     * @return \Bakery\Types\Definitions\ReferenceType
     */
    public function type(string $name): ReferenceType
    {
        return new ReferenceType($name);
    }
}
