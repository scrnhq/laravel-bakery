<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserSchema extends ModelSchema
{
    protected $model = User::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
            'email' => Bakery::string()->unique(),
            'type' => Bakery::string()->canStoreWhen('setType'),
            'password' => Bakery::string()->policy('readPassword'),
            'secret_information' => Bakery::string()
                ->policy(function (?Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                })->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Bakery::collection(ArticleSchema::class)
                ->policy(function (?Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                }),
            'customRoles' => Bakery::collection(RoleSchema::class),
            'phone' => Bakery::model(PhoneSchema::class),
        ];
    }
}
