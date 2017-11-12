<?php

namespace Scrn\Bakery\Support;

use Illuminate\Support\Fluent;

class Field extends Fluent
{
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
     * Return the default types.
     *
     * @return null
     */
    public function type()
    {
        return null;
    }

    /**
     * Return the default args.
     *
     * @return array
     */
    public function args(): array
    {
        return [];
    }

    /**
     * Get the attributes for the field. 
     *
     * @return array
     */
    public function getAttributes()
    {
        $args = $this->args();
        $type = $this->type();
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'args' => $args
        ], $attributes);

        if (isset($type)) {
            $attributes['type'] = $type;
        }

        if (method_exists($this, 'resolve')) {
            $attributes['resolve'] = [$this, 'resolve'];
        }

        return $attributes;
    }

    /**
     * Convert the field to an array. 
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }
}
