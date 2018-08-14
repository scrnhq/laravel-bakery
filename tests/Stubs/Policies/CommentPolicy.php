<?php

namespace Bakery\Tests\Stubs\Policies;

class CommentPolicy
{
    public function create(): bool
    {
        return true;
    }

    public function setArticle(): bool
    {
        return true;
    }

    public function createUser(): bool
    {
        return true;
    }

    public function setUser(): bool
    {
        return true;
    }

    public function createUpvotes(): bool
    {
        return true;
    }

    public function setUpvotes(): bool
    {
        return true;
    }
}
