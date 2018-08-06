<?php

namespace Bakery\Types\Definitions;

use GraphQL\Type\Definition\Type as BaseType;
use GraphQL\Type\Definition\EnumType as BaseEnumType;

class EnumType extends Type implements NamedType
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
     * Convert the Bakery type to a GraphQL type.
     *
     * @param array $options
     * @return BaseType
     */
    public function toType(array $options = []): BaseType
    {
        $values = $this->values();

        return new BaseEnumType([
            'name' => $this->name(),
            'values' => empty($values) ? null : $values,
        ]);
    }
}
