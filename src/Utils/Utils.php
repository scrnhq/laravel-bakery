<?php

namespace Bakery\Utils;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Bakery\Exceptions\InvariantViolation;
use Illuminate\Database\Eloquent\Relations;

class Utils
{
    /**
     * @param $test
     * @param string $message
     * @param mixed $sprintfParam1
     * @param mixed $sprintfParam2 ...
     * @throws Error
     */
    public static function invariant($test, $message = '')
    {
        if (! $test) {
            if (func_num_args() > 2) {
                $args = func_get_args();
                array_shift($args);
                $message = call_user_func_array('sprintf', $args);
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

    public static function toFieldArray($field)
    {
        if (is_array($field)) {
            return $field;
        }

        return [
            'type' => $field,
        ];
    }

    public static function pluralRelationship($relationship)
    {
        return $relationship instanceof Relations\HasMany ||
               $relationship instanceof Relations\BelongsToMany;
    }

    public static function singularRelationship($relationship)
    {
        return $relationship instanceof Relations\BelongsTo ||
               $relationship instanceof Relations\HasOne;
    }

    public static function nullifyField($field): array
    {
        $field = self::toFieldArray($field);
        $field['type'] = Type::getNamedType($field['type']);

        return $field;
    }

    public static function nullifyFields($fields): Collection
    {
        return collect($fields)->map(function ($field) {
            $field = self::nullifyField($field);

            return $field;
        });
    }
}
