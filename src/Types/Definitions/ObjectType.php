<?php

namespace Bakery\Types\Definitions;

use Illuminate\Support\Collection;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\ObjectType as GraphQLObjectType;

class ObjectType extends Type implements NamedType
{
    /**
     * Define the fields for the type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Get the fields for the type.
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        $fields = collect($this->fields());

        return $fields->map(function (Type $field, $name) {
            return $field->toField();
        });
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = [
           'name' => $this->name(),
           'resolver' => $this->getResolver(),
           'fields' => function () {
               return $this->getFields()->toArray();
           },
       ];

        return $attributes;
    }

    /**
     * Convert the type to a GraphQL Type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return new GraphQLObjectType($this->getAttributes());
    }
}
