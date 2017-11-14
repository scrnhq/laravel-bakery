<?php

namespace Scrn\Bakery\Types;

use Illuminate\Support\Fluent;

class Field extends Fluent
{
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
     * @return null
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
    protected function getResolver()
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
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'args' => $this->args(),
        ], $attributes);

        $type = $this->type();
        if (isset($type)) {
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if ($resolver) {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
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
}
