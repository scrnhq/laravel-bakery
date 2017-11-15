<?php

namespace Scrn\Bakery\Traits;

use ErrorException;
use ReflectionClass;
use ReflectionMethod;

trait GraphQLResource
{
    /**
     * The fields exposed in GraphQL.
     *
     * @return array
     */
    public function fields(): array
    {
        return [];
    }

    public function lookupFields(): array
    {
        return [];
    }

    public function relations(): array
    {
        return [];
    }
}
