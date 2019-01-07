<?php

namespace Bakery\Tests\Fixtures\Policies;

class TagPolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.tag.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.tag.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.tag.deletable'] ?? true;
    }
}
