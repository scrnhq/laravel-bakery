<?php

namespace Bakery\Support;

use ArrayObject;

class Arguments extends ArrayObject
{
    /**
     * Arguments constructor.
     *
     * @param  array  $args
     */
    public function __construct(array $args)
    {
        $data = [];

        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $value = new self($value);
            }

            $data[$key] = $value;
        }

        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (! $this->offsetExists($offset)) {
            return null;
        }

        return parent::offsetGet($offset);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * @return array
     */
    public function getArrayCopy(): array
    {
        $array = parent::getArrayCopy();

        foreach ($array as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->getArrayCopy();
            }
        }

        return $array;
    }

    /**
     * Return if the arguments are empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->toArray());
    }
}
