<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\UserRole;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class UserRoleDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = UserRole::class;

    public function fields(): array
    {
        return [
            'comment' => Bakery::string(),
        ];
    }
}
