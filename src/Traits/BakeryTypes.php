<?php

namespace Bakery\Traits;

use Bakery\Types\Definitions\ObjectType;
use Bakery\Types\Definitions\EloquentType;
use Bakery\Types\Definitions\ReferenceType;
use Bakery\Types\Definitions\PolymorphicType;
use GraphQL\Type\Definition\Type as GraphQLType;

trait BakeryTypes
{
    public function string(): ReferenceType
    {
        return new ReferenceType(GraphQLType::string());
    }

    public function int(): ReferenceType
    {
        return new ReferenceType(GraphQLType::int());
    }

    public function ID(): ReferenceType
    {
        return new ReferenceType(GraphQLType::ID());
    }

    public function boolean(): ReferenceType
    {
        return new ReferenceType(GraphQLType::boolean());
    }

    public function float(): ReferenceType
    {
        return new ReferenceType(GraphQLType::float());
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
        return new ReferenceType($this->resolve($name));
    }
}
