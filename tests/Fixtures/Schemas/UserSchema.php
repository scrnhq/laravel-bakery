<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\User;

class UserSchema extends ModelSchema
{
    protected $model = User::class;

    public function fields(): array
    {
        return [
            'name' => Field::string()->searchable(),
            'email' => Field::string()->unique()->searchable(),
            'admin' => Field::boolean()->canStoreWhen('setAdmin'),
            'password' => Field::string()->canSee(function () {
                return $_SERVER['graphql.user.canSeePassword'] ?? true;
            }),
            'restricted' => Field::string()->canSeeWhen('viewRestricted')->canStoreWhen('storeRestricted')->nullable(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Field::collection(ArticleSchema::class)->canSee(function () {
                return $_SERVER['graphql.user.canSeeArticles'] ?? true;
            }),
            'roles' => Field::collection(RoleSchema::class),
            'noPivotRoles' => Field::collection(RoleSchema::class),
            'phone' => Field::model(PhoneSchema::class),
        ];
    }
}
