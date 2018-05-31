<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Tests\Models\Role;
use Bakery\Eloquent\ModelSchema;
use GraphQL\Type\Definition\Type;

class RoleBakery
{
    use ModelSchema;

    public static $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Type::nonNull(Type::string()),
        ];
    }
}
