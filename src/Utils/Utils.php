<?php

namespace Bakery\Utils;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use GraphQL\Type\Definition\NonNull;
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

    public static function normalizeFields($fields): Collection
    {
        return collect($fields)->map(function ($field) {
            return self::toFieldArray($field);
        });
    }

    public static function nullifyField($field): array
    {
        $field = self::toFieldArray($field);

        if ($field['type'] instanceof NonNull) {
            $field['type'] = $field['type']->getWrappedType();
        }

        return $field;
    }

    public static function nullifyFields($fields): Collection
    {
        return collect($fields)->map(function ($field) {
            $field = self::nullifyField($field);

            return $field;
        });
    }

    public static function swapWrappedType($field, Type $swap): Type
    {
        $field = self::toFieldArray($field);
        $listOf = $field['type'] instanceof ListOf;
        $nonNull = $field['type'] instanceof NonNull;

        if ($listOf && $nonNull) {
            return Type::nonNull(Type::listOf($swap));
        }
        if ($nonNull) {
            return Type::nonNull($swap);
        }
        if ($listOf) {
            return Type::listOf($swap);
        }
    }

    public static function usesTrait($class, string $trait)
    {
        $traits = class_uses_deep($class, true);

        return in_array($trait, $traits);
    }
}
