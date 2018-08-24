<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Tests\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserPolicy
{
    public function create(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }

    public function setTypeAttribute(): bool
    {
        return false;
    }

    public function createPhone(): bool
    {
        return true;
    }

    public function setPhone(): bool
    {
        return true;
    }

    public function createCustomRoles(): bool
    {
        return true;
    }

    public function setCustomRoles(): bool
    {
        return true;
    }

    public function createArticles(): bool
    {
        return true;
    }

    public function setEmail()
    {
        return true;
    }

    public function setType()
    {
        return false;
    }

    public function readPassword(Authenticatable $viewer, User $user): bool
    {
        return $viewer && $user->is($viewer);
    }
}
