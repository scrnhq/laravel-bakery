<?php

namespace Bakery\Types;

use Bakery\Utils\Utils;
use Illuminate\Support\Str;

abstract class CustomEntityType extends EntityType
{
    /**
     * If no name is specified fall back on an
     * automatically generated name based on the class name.
     *
     * @return string
     */
    public function name(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return Utils::typename(Str::before(class_basename($this), 'BakeField'));
    }
}
