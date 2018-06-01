<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Eloquent\Introspectable;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserBakery
{
    use Introspectable;

    public static $model = User::class;

    public $lookupFields = ['email'];

    public function fields(): array
    {
        return [
            'name' => Type::nonNull(Type::string()),
            'email' => Type::nonNull(Type::string()),
            'type' => Type::nonNull(Type::string()),
            'password' => [
                'type' => Type::nonNull(Type::string()),
                'policy' => 'readPassword',
            ],
            'secret_information' => [
                'type' => Type::string(),
                'policy' => function (User $user, $args, Authenticatable $viewer = null) {
                    return $viewer && $user->is($viewer);
                },
            ],
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => [
                'type' => Type::nonNull(Type::listOf(Bakery::type('Article'))),
                'policy' => function (User $user, $args, Authenticatable $viewer = null) {
                    return $viewer && $user->is($viewer);
                },
            ],
            'phone' => Type::nonNull(Bakery::type('Phone')),
            'roles' => Type::nonNull(Type::listOf(Bakery::type('Role'))),
        ];
    }
}
