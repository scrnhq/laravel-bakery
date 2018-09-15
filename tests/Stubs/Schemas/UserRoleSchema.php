<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\UserRole;

class UserRoleSchema extends ModelSchema
{
    protected $model = UserRole::class;

    public function fields(): array
    {
        return [
            'comment' => Bakery::string(),
        ];
    }
}
