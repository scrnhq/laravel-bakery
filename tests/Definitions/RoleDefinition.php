<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Role;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class RoleDefinition
{
    use Introspectable;

    public static $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'users' => Bakery::collection(UserDefinition::class),
        ];
    }
}
