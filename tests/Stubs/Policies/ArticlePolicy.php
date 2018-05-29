<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Models\User;
use Bakery\Test\Models\Article;

class ArticlePolicy
{
    /**
     * Determine if an article can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if an article can be updated by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return true;
    }

    /**
     * Determine if an article can be deleted by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return true;
    }

    public function createUser(User $user): bool
    {
        return true;
    }

    public function setUser(User $user): bool
    {
        return true;
    }

    public function createComments(User $user): bool
    {
        return true;
    }
}
