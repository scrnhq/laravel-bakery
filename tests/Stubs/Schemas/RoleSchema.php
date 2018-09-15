<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Role;

class RoleSchema extends ModelSchema
{
    protected $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'users' => Bakery::collection(UserSchema::class),
        ];
    }
}
