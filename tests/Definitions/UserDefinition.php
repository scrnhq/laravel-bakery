<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\User;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class UserDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = User::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
            'email' => Bakery::string()->unique(),
            'type' => Bakery::string()->canStoreWhen('setType'),
            'password' => Bakery::string()->policy('readPassword'),
            'secret_information' => Bakery::string()
                ->policy(function (Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                })->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Bakery::collection(ArticleDefinition::class)
                ->policy(function (Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                }),
            'customRoles' => Bakery::collection(RoleDefinition::class),
            'phone' => Bakery::model(PhoneDefinition::class),
        ];
    }
}
