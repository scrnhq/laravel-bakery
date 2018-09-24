<?php

namespace Bakery\Utils;

use Bakery\Exceptions\InvariantViolation;
use Illuminate\Database\Eloquent\Relations;

class Utils
{
    /**
     * @param $test
     * @param string $message
     * @param array $args
     */
    public static function invariant($test, $message = '', ...$args)
    {
        if (! $test) {
            if (count($args)) {
                $message = sprintf($message, $args);
            }

            throw new InvariantViolation($message);
        }
    }

    public static function single($class)
    {
        return camel_case(str_singular(class_basename($class)));
    }

    public static function plural($class)
    {
        return camel_case(str_plural(class_basename($class)));
    }

    public static function typename($class)
    {
        return studly_case(str_singular(class_basename($class)));
    }

    public static function pluralTypename($class)
    {
        return studly_case(str_plural(class_basename($class)));
    }
}
