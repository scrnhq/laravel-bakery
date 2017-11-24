<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\EnumType as BaseEnumType;
use GraphQL\Type\Definition\Type as BaseType;

class EnumType extends Type
{
    /**
     * Return the values for the enum.
     *
     * @return array
     */
    public function values(): array
    {
        return [];
    }

    /**
     * Get the values from the container.
     *
     * @return array
     */
    public function getValues()
    {
        $values = $this->values();
        $attributesValues = array_get($this->attributes, 'values', []);
        return sizeof($attributesValues) ? $attributesValues : $values;
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        $attributes['values'] = $this->getValues();

        return $attributes;
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @param array $options
     * @return BaseType
     */
    public function toGraphQLType(array $options = []): BaseType
    {
        return new BaseEnumType($this->toArray());
    }
}
