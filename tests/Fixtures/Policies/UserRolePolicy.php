<?php

namespace Bakery\Tests\Fixtures\Policies;

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

    public function createTag(): bool
    {
        return true;
    }
}
