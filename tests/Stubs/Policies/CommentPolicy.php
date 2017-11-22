<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Stubs\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function setPost(User $user): bool
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
}
