<?php

namespace Bakery;

use Bakery\Fields\PolymorphicField;

class Field
{
    /**
     * Get the current bound registry.
     */
    public static function getRegistry(): TypeRegistry
    {
        return resolve(TypeRegistry::class);
    }

    public static function string(): Fields\Field
    {
        return self::getRegistry()->field(self::getRegistry()->string());
    }

    public static function int(): Fields\Field
    {
        return self::getRegistry()->field(self::getRegistry()->int());
    }

    public static function ID(): Fields\Field
    {
        return self::getRegistry()->field(self::getRegistry()->ID());
    }

    public static function boolean(): Fields\Field
    {
        return self::getRegistry()->field(self::getRegistry()->boolean());
    }

    public static function float(): Fields\Field
    {
        return self::getRegistry()->field(self::getRegistry()->float());
    }

    public static function model(string $class): Fields\Field
    {
        return self::getRegistry()->eloquent($class);
    }

    public static function collection(string $class): Fields\Field
    {
        return self::model($class)->list();
    }

    public static function type(string $type): Fields\Field
    {
        return self::getRegistry()->field($type);
    }

    public static function list(string $type): Fields\Field
    {
        return self::type($type)->list();
    }

    public static function polymorphic(array $modelSchemas): PolymorphicField
    {
        return self::getRegistry()->polymorphic($modelSchemas);
    }
}
