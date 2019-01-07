<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserSchema extends ModelSchema
{
    protected $model = User::class;

    public function fields(): array
    {
        return [
            'name' => Field::string()->searchable(),
            'email' => Field::string()->unique()->searchable(),
            'admin' => Field::boolean()->canStoreWhen('setAdmin'),
            'password' => Field::string()->canSeeWhen('readPassword'),
            'secret_information' => Field::string()
                ->canSee(function (?Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                })->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Field::collection(ArticleSchema::class)
                ->canSee(function (?Authenticatable $user, User $source) {
                    return $user && $source->is($user);
                }),
            'customRoles' => Field::collection(RoleSchema::class),
            'phone' => Field::model(PhoneSchema::class),
        ];
    }
}
