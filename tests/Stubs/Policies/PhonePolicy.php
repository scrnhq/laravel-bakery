<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Test\Models\Phone;
use Bakery\Tests\Models\User;

class PhonePolicy
{
    /**
     * Determine if a phone can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function createUser(User $user): bool
    {
        return true;
    }
}
