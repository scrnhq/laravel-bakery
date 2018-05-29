<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Tests\Models\Role;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

class RoleBakery extends BakeryModel
{
    protected $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Type::nonNull(Type::string()),
        ];
    }
}
