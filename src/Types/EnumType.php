<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\Type as BaseType;
use GraphQL\Type\Definition\EnumType as BaseEnumType;

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

        return count($attributesValues) ? $attributesValues : $values;
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $values = $this->getValues();

        $attributes['values'] = empty($values) ? null : $values;

        return $attributes;
    }

    /**
     * Convert the Bakery type to a GraphQL type.
     *
     * @param array $options
     * @return BaseType
     */
    public function toType(array $options = []): BaseType
    {
        return new BaseEnumType($this->toArray());
    }
}
