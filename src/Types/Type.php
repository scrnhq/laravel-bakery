<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as BaseType;

class Type
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
            $resolver = [$this, $resolveMethod];
            return function () use ($resolver) {
                $args = func_get_args();
                return call_user_func_array($resolver, $args);
            };
        }

        return null;
    }

    /**
     * Get the fields for the type.
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = collect($this->fields());

        return $fields->map(function ($field, $name) {
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
        $attributes = [
            'name' => $this->name,
            'fields' => function () {
                return $this->getFields();
            }
        ];

        if (method_exists($this, 'resolveField')) {
            $attributes['resolveField'] = [$this, 'resolveField'];
        }

        return $attributes;
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
     * Convert the Bakery type to a GraphQL type.
     *
     * @return BaseType
     */
    public function toGraphQLType(): BaseType
    {
        return $this->type = new ObjectType($this->toArray());
    }

    /**
     * Dynamically get properties on the object.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        } elseif (property_exists($this, $key)) {
            return $this->{$key};
        }

        return null;
    }
}
