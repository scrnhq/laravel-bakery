<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Role;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class RoleDefinition
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
            'users' => Bakery::collection(UserDefinition::class),
        ];
    }
}
