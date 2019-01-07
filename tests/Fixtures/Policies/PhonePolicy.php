<?php

namespace Bakery\Tests\Fixtures\Policies;

class PhonePolicy
{
    public function create(): bool
    {
        return $_SERVER['graphql.phone.creatable'] ?? true;
    }

    public function update(): bool
    {
        return $_SERVER['graphql.phone.updatable'] ?? true;
    }

    public function delete(): bool
    {
        return $_SERVER['graphql.phone.deletable'] ?? true;
    }
}
