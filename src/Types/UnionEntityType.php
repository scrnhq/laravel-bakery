<?php

namespace Bakery\Types;

use Bakery\Types\Concerns\InteractsWithPolymorphism;
use Bakery\Types\Definitions\RootType;
use Bakery\Types\Definitions\UnionType;
use GraphQL\Type\Definition\ResolveInfo;

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
        return collect($this->modelSchemas)->map(function ($modelSchema) {
            return $modelSchema instanceof RootType
                ? $modelSchema
                : $this->registry->type($this->registry->getModelSchema($modelSchema)->typename());
        })->toArray();
    }

    /**
     * Receives $value from resolver of the parent field and returns concrete Object BakeField for this $value.
     *
     * @param $value
     * @param $context
     * @param ResolveInfo $info
     * @return mixed
     * @throws \Bakery\Exceptions\TypeNotFound
     */
    public function resolveType($value, $context, ResolveInfo $info)
    {
        return $this->registry->resolve($this->registry->getSchemaForModel($value)->typename());
    }
}
