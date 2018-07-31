<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Tests\Models\UserRole;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;

class UserRoleDefinition
{
    use Introspectable;

    public static $model = UserRole::class;

    public function fields(): array
    {
        return [
            'comment' => Type::string(),
        ];
    }

    public function pivotRelations(): array
    {
        return [
            'users' => UserBakery::class,
            'roles' => RoleBakery::class,
        ];
    }
}
