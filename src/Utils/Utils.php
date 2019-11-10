<?php

namespace Bakery\Utils;

use Bakery\Exceptions\InvariantViolation;
use Illuminate\Support\Str;

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
        return Str::camel(Str::singular(class_basename($class)));
    }

    public static function plural($class)
    {
        return Str::camel(Str::plural(class_basename($class)));
    }

    public static function typename($class)
    {
        return Str::studly(Str::singular(class_basename($class)));
    }

    public static function pluralTypename($class)
    {
        return Str::studly(Str::plural(class_basename($class)));
    }
}
