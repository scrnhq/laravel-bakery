<?php

namespace Bakery\Tests\Stubs\Policies;

class UpvotePolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function setUpvoteable(): bool
    {
        return true;
    }

    public function createUpvoteable(): bool
    {
        return true;
    }
}
