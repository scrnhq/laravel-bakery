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

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
            'email' => Bakery::string()->unique(),
            'type' => Bakery::string(),
            'password' => Bakery::string()->policy('readPassword'),
            'secret_information' => Bakery::string()
                ->policy(function (User $user, $args, Authenticatable $viewer = null) {
                    return $viewer && $user->is($viewer);
                })->nullable(),
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
