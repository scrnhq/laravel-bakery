<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

use Bakery\Tests\Models\Role;

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
