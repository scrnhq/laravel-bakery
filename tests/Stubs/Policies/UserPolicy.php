<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Stubs\User;

class UserPolicy
{
    /**
     * Determine if a user can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if a user can be updated by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return true;
    }
}
