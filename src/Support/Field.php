<?php

namespace Bakery\Support;

use Bakery\Contracts\FieldContract;
use Bakery\Exceptions\InvalidFieldException;

abstract class Field implements FieldContract
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
     * The type of the Field.
     *
     * @return mixed
     */
    public function type()
    {
        return null;
    }

    /**
     * The arguments for the Field.
     *
     * @return array
     */
    public function args()
    {
        return [];
    }

    /**
     * Retrieve the resolver for the Field.
     *
     * @return Callback|null
     */
    private function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
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
        if (!$this->name) {
            throw new InvalidFieldException('Required property name missing for Field.');
        }

        return [
            'name' => $this->name,
            'args' => $this->args,
            'type' => $this->type,
            'fields' => $this->fields,
            'description' => $this->description,
            'resolve' => $this->getResolver(),
        ];
    }

    /**
     * Convert the Field instance to an array.
     *
     * @return array
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
