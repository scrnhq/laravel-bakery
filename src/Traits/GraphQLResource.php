<?php

namespace Scrn\Bakery\Traits;

use GraphQL\Type\Definition\ObjectType;

trait GraphQLResource
{
    /**
     * Returns the fields for the model
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     *
     *
     * @return ObjectType
     */
    public function toObjectType(): ObjectType
    {
        $attributes = [
            'name' => class_basename($this),
            'fields' => $this->fields(),
        ];

        return new ObjectType($attributes);
    }
}