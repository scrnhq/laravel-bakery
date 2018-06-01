<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
    
    public function setTypeAttribute(User $user): bool
    {
        return false;
    }

    /**
     * Determine if a phone can be created through the user.
     *
     * @param User $user
     * @return bool
     */
    public function createPhone(User $user): bool
    {
        return true;
    }

    public function setPhone(User $user): bool
    {
        return true;
    }

    public function createRoles(User $user): bool
    {
        return true;
    }

    public function setRoles(User $user): bool
    {
        return true;
    }

    public function createArticles(User $user): bool
    {
        return true;
    }

    public function readPassword(Authenticatable $viewer, User $user): bool
    {
        return $viewer && $user->is($viewer);
    }
}
