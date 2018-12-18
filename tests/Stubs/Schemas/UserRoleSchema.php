<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\UserRole;

class UserRoleSchema extends ModelSchema
{
    protected $model = UserRole::class;

    public function fields(): array
    {
        return [
            'comment' => Field::string()->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'tag' => Field::model(TagSchema::class)->nullable(),
        ];
    }
}
