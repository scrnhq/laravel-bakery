<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Mutations\CreateMutation;
use Bakery\Queries\EntityQuery;
use Bakery\Support\Schema;
use Bakery\Tests\Stubs;

class BlogSchema extends Schema
{
    protected $models = [
        Stubs\Post::class,
        Stubs\Comment::class,
        Stubs\User::class,
        Stubs\Role::class,
        Stubs\Phone::class,
    ];

    protected $queries = [
        'phone' => OverridePhoneQuery::class,
    ];

    protected $mutations = [
        'createPhone' => OverrideCreatePhoneMutation::class,
    ];
}

class OverridePhoneQuery extends EntityQuery
{
    public function __construct(array $attributes = [])
    {
        parent::__construct(Stubs\Phone::class, $attributes);
    }
}

class OverrideCreatePhoneMutation extends CreateMutation
{
    public function __construct()
    {
        parent::__construct(Stubs\Phone::class);
    }
}
