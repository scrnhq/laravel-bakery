<?php

namespace Bakery\Types\Definitions;

use Bakery\Fields\Field;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;

class InputType extends ObjectType
{
    /**
     * Get the fields for the type.
     *
     * @return Collection
     */
    public function getFields(): Collection
    {
        $fields = collect($this->fields());

        return $fields->map(function (Field $field) {
            return $field->getType()->toType();
        });
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name(),
            'fields' => [$this, 'resolveFields'],
        ];
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @param array $options
     * @return GraphQLType
     */
    public function toType(array $options = []): GraphQLType
    {
        return new InputObjectType($this->getAttributes());
    }
}
