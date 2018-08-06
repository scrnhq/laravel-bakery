<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Support\Collection;

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

        return $fields->map(function (Type $field) {
            return $field->toInputField();
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
            'fields' => function () {
                return $this->getFields()->map(function ($field) {
                    $field['resolve'] = null;

                    return $field;
                })->toArray();
            },
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
