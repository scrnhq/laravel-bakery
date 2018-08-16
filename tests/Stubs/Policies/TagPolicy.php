<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
