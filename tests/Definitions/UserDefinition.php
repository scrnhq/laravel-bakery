<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\User;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserDefinition
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
            'articles' => Bakery::collection(ArticleDefinition::class)
                ->policy(function (User $user, $args, Authenticatable $viewer = null) {
                    return $viewer && $user->is($viewer);
                }),
            'roles' => Bakery::collection(RoleDefinition::class),
            'phone' => Bakery::model(PhoneDefinition::class),
        ];
    }
}
