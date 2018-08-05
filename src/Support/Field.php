<?php

namespace Bakery\Support;

use Bakery\Types\Definitions\Type;
use Bakery\Exceptions\InvalidFieldException;
use GraphQL\Type\Definition\Type as GraphQLType;

abstract class Field
{
    /**
     * The attributes of the Field.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The attributes of the Field.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Define the type of the Field.
     *
     * @return Type
     */
    abstract public function type(): Type;

    /**
     * Get the underlying field of the type and convert it to a type.
     *
     * @return GraphQLType
     */
    public function getType(): GraphQLType
    {
        return $this->type()->toType();
    }

    /**
     * The arguments for the Field.
     *
     * @return array
     */
    public function args(): array
    {
        return [];
    }

    /**
     * Get the arguments of the field and convert them to types.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return collect($this->args())->map(function (Type $field) {
            return $field->toType();
        })->toArray();
    }

    /**
     * Retrieve the resolver for the Field.
     *
     * @return callable|null
     */
    private function getResolver()
    {
        if (! method_exists($this, 'resolve')) {
            return null;
        }

        return [$this, 'resolve'];
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     * @throws InvalidFieldException
     */
    public function getAttributes()
    {
        if (! $this->name) {
            throw new InvalidFieldException('Required property name missing for Field.');
        }

        return [
            'name' => $this->name,
            'args' => $this->getArgs(),
            'type' => $this->getType(),
            'fields' => $this->fields,
            'description' => $this->description,
            'resolve' => $this->getResolver(),
        ];
    }

    /**
     * Convert the Field instance to an array.
     *
     * @return array
     * @throws InvalidFieldException
     */
    public function toArray()
    {
        return $this->getAttributes();
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
