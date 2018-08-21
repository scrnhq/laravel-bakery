<?php

namespace Bakery\Tests\Stubs\Policies;

class RolePolicy
{
    public function create()
    {
        return true;
    }

    // Don't use this policy because we use it to test if a mutation fails
    // if the policy is not defined.
    //
    // public function update()
    // {
    //     return true;
    // }

    public function createUsers()
    {
        return true;
    }

    public function setUsers()
    {
        return true;
    }
}
