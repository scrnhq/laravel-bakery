<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\UserRole;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class UserRoleDefinition
{
    use Introspectable;

    public static $model = UserRole::class;

    public function fields(): array
    {
        return [
            'comment' => Bakery::string(),
        ];
    }
}
