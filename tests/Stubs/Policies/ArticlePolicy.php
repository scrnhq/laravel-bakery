<?php

namespace Bakery\Tests\Stubs\Policies;

class ArticlePolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function delete(): bool
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

    public function setCategory(): bool
    {
        return true;
    }

    public function createComments(): bool
    {
        return true;
    }

    public function setTags(): bool
    {
        return true;
    }
}
