<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Field;
use Bakery\Tests\Fixtures\Models\UserRole;

class UserRoleSchema extends ModelSchema
{
    protected $model = UserRole::class;

    public function fields(): array
    {
        return [
            'admin' => Field::boolean()->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'tag' => Field::model(TagSchema::class)->nullable(),
        ];
    }
}
