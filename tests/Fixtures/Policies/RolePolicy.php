<?php

namespace Bakery\Tests\Fixtures\Policies;

class RolePolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.role.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.role.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.role.deletable'] ?? true;
    }
}
