<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\Role;

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
            'users' => Field::collection(UserSchema::class)
                ->inverse($_SERVER['eloquent.user.roles.inverseRelation'] ?? 'roles'),
        ];
    }
}
