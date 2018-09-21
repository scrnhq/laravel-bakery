<?php

namespace Bakery\Types\Definitions;

use Bakery\Fields\Field;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\ObjectType as GraphQLObjectType;

class ObjectType extends Type implements NamedType
{
    /**
     * The fields of the object type.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Define the fields for the type.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * Get the fields for the type.
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        $fields = collect($this->fields());

        return $fields->map(function (Field $field) {
            return $field->toArray();
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
            'fields' => [$this, 'resolveFields'],
        ];

        return $attributes;
    }

    /**
     * Resolve the fields.
     *
     * @return array
     */
    public function resolveFields(): array
    {
        return $this->getFields()->toArray();
    }

    /**
     * Convert the type to a GraphQL type.
     *
     * @return GraphQLType
     */
    public function toType(): GraphQLType
    {
        return new GraphQLObjectType($this->getAttributes());
    }
}
