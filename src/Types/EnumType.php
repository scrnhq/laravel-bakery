<?php

namespace Scrn\Bakery\Types;

use GraphQL\Type\Definition\Type as BaseType;
use GraphQL\Type\Definition\EnumType as BaseEnumType;

class EnumType extends Type
{
    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'values' => $this->values(), 
        ], $attributes);

        return $attributes;
    }

    /**
     * Return the values for the enum.
     *
     * @return array
     */
    protected function values(): array
    {
        return [];
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @return ObjectType
     */
    public function toGraphQLType(): BaseType
    {
        return new BaseEnumType($this->toArray());
    }
}
