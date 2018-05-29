<?php

namespace Bakery\Tests\Stubs\Policies;

use Bakery\Test\Stubs\Model;
use Bakery\Tests\Stubs\User;

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
