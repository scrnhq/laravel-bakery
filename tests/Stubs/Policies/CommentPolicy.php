<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Stubs\User;
use Bakery\Test\Stubs\Comment;

class CommentPolicy
{
    /**
     * Determine if a comment can be created by the user.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }
}
