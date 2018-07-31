<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Tests\Models\Role;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class RoleBakery
{
    use Introspectable;

    public static $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [
            'users' => Type::listOf(Bakery::type('User')),
        ];
    }
}
