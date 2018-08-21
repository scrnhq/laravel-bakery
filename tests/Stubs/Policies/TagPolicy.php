<?php

namespace Bakery\Tests\Stubs\Policies;

class TagPolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function createArticles(): bool
    {
        return true;
    }

    public function setArticles(): bool
    {
        return true;
    }
}
