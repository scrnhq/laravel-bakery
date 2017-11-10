<?php

namespace Scrn\Bakery\Traits;

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
}
