<?php

namespace Bakery\Tests\Stubs\Policies;

class UserRolePolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function setTag(): bool
    {
        return true;
    }
}
