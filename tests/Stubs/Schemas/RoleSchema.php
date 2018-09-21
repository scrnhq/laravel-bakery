<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\Role;

class RoleSchema extends ModelSchema
{
    protected $model = Role::class;

    public function fields(): array
    {
        return [
            'name' => Field::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'users' => Field::collection(UserSchema::class),
        ];
    }
}
