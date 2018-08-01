<?php

namespace Bakery\Tests\Stubs\Policies;

class RolePolicy
{
    public function create()
    {
        return true;
    }

    public function createUsers()
    {
        return true;
    }

    public function setUsers()
    {
        return true;
    }
}
