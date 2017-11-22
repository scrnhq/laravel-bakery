<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Stubs\User;
use Bakery\Tests\Stubs\Role;

class RolePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function createUsers(User $user, Role $role): bool
    {
        return true;
    }
}
