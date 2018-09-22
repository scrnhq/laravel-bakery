<?php

namespace Bakery;

use Bakery\Support\TypeRegistry;
use Bakery\Types\Definitions\InternalType;

class Type
{
    /**
     * Get the current bound registry.
     */
    public static function getRegistry(): TypeRegistry
    {
        return resolve(TypeRegistry::class);
    }

    /**
     * @return \Bakery\Types\Definitions\InternalType
     */
    public static function string(): InternalType
    {
        return self::getRegistry()->string();
    }

    /**
     * @return \Bakery\Types\Definitions\InternalType
     */
    public static function int(): InternalType
    {
        return self::getRegistry()->int();
    }

    /**
     * @return \Bakery\Types\Definitions\InternalType
     */
    public static function ID(): InternalType
    {
        return self::getRegistry()->ID();
    }

    /**
     * @return \Bakery\Types\Definitions\InternalType
     */
    public static function boolean(): InternalType
    {
        return self::getRegistry()->boolean();
    }

    /**
     * @return \Bakery\Types\Definitions\InternalType
     */
    public static function float(): InternalType
    {
        return self::getRegistry()->float();
    }

    /**
     * Create a new type by reference.
     *
     * @param string $name
     * @return \Bakery\Types\Definitions\Type
     */
    public static function of(string $name): Types\Definitions\Type
    {
        return self::getRegistry()->type($name);
    }

    /**
     * Create a new type for a model schema.
     *
     * @param string $class
     * @return \Bakery\Types\Definitions\Type
     */
    public static function modelSchema(string $class): Types\Definitions\Type
    {
        $modelSchema = self::getRegistry()->getModelSchema($class);
        return self::getRegistry()->type($modelSchema->getTypename());
    }
}
