<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Eloquent\Mutable;
use Bakery\Tests\Models\UserRole;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
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
