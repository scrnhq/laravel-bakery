<?php

namespace Scrn\Bakery\Types;

use Illuminate\Support\Fluent;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as BaseType;

class Type extends Fluent
{
    /**
     * Return the default fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Return the default attributes.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get the fields for the type.
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = collect($this->fields());

        return collect($this->fields())->map(function ($field, $name) {
            $resolver = $this->getFieldResolver($name, $field);

            if (is_array($field)) {
                $field['resolve'] = $resolver;
                return $field;
            }

            return [
                'name' => $name,
                'type' => $field,
                'resolve' => $resolver,
            ];
        })->toArray();
    }

    /**
     * Get the attributes for the type.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'fields' => function () {
                return $this->getFields();
            },
        ], $attributes);

        return $attributes;
    }

    /**
     * Get a dynamic field resolver.
     *
     * @param string $name
     * @param mixed $field
     * @return mixed
     */
    protected function getFieldResolver(string $name, $field)
    {
        $resolveMethod = 'resolve' . studly_case($name) . 'Field';

        if (method_exists($this, $resolveMethod)) {
            $resolver = array($this, $resolveMethod);

            return function () use ($resolver) {
                $args = func_get_args();
                return call_user_func_array($resolver, $args);
            };
        }
        
        return null;
    }

    /**
     * Convert the type to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Conver the Bakery type to a GraphQL type.
     *
     * @return ObjectType
     */
    public function toGraphQLType(): BaseType
    {
        return new ObjectType($this->toArray());
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key] : null;
    }
    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]);
    }
}
