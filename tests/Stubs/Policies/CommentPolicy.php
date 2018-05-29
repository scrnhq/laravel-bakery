<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function setArticle(User $user): bool
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
