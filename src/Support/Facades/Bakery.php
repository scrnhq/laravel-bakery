<?php

namespace Bakery\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Bakery\Types\Definitions\ReferenceType string
 * @method static \Bakery\Types\Definitions\ReferenceType int
 * @method static \Bakery\Types\Definitions\ReferenceType boolean
 * @method static \Bakery\Types\Definitions\ReferenceType float
 * @method static \Bakery\Types\Definitions\ReferenceType ID
 * @method static \Bakery\Types\Definitions\EloquentType model(string $definition)
 * @method static \Bakery\Types\Definitions\EloquentType collection(string $definition)
 * @method static \Bakery\Types\Definitions\ReferenceType list(string $definition)
 * @method static \Bakery\Types\Definitions\ReferenceType type(string $definition)
 * @method static \Bakery\Types\Definitions\PolymorphicField polymorphic(array $definitions)
 * @method static \GraphQL\Type\Definition\NamedType resolve(string $definition)
 * @method static \Bakery\Eloquent\ModelSchema getModelSchema(string $class)
 * @method static bool hasSchemaForModel(\Illuminate\Database\Eloquent\Model|string $model)
 * @method static \Bakery\Eloquent\ModelSchema getSchemaForModel(\Illuminate\Database\Eloquent\Model|string $model)
 */
class Bakery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bakery\Bakery::class;
    }
}
