<?php

namespace Bakery\Tests\Stubs;

use Bakery\Support\Schema;

class BlogSchema extends Schema
{
    protected $models = [
        Post::class,
        Comment::class,
        User::class,
        Role::class,
        Phone::class,
    ];
}
