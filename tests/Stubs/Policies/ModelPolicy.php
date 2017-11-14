<?php

namespace Scrn\Bakery\Tests\Stubs\Policies;

use Scrn\Bakery\Tests\Stubs\User;
use Scrn\Bakery\Test\Stubs\Model;

class ModelPolicy
{
    /**
     * Determine if the given model can be updated by the user.
     *
     * @param  User  $model
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }
}
