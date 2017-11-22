<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Stubs\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function setPostId(User $user): bool
    {
        return true;
    }

    public function createUser(User $user): bool
    {
        return true;
    }

    public function setUserId(User $user): bool
    {
        return true;
    }
}
