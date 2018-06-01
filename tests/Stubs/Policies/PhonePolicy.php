<?php

namespace Bakery\Tests\Stubs\Policies;

class PhonePolicy
{
    public function create(): bool
    {
        return true;
    }

    public function createUser(): bool
    {
        return true;
    }
}
