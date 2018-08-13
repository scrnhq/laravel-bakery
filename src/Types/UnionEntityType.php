<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\UnionType;
use GraphQL\Type\Definition\ResolveInfo;
use Bakery\Types\Concerns\InteractsWithPolymorphism;

class UnionEntityType extends UnionType
{
    use InteractsWithPolymorphism;

    /**
     * Get the types of the union type.
     *
     * @return array
     */
    public function types(): array
    {
        return collect($this->definitions)->map(function (string $definition) {
            return Bakery::type(resolve($definition)->typename());
        })->toArray();
    }

    /**
     * Receives $value from resolver of the parent field and returns concrete Object Type for this $value.
     *
     * @param $value
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveType($value, $context, ResolveInfo $info)
    {
        return Bakery::resolveDefinitionType($value);
    }
}
