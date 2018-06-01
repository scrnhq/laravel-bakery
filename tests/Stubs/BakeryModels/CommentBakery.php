<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Tests\Models\Comment;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;

class CommentBakery
{
    use Introspectable;

    public static $model = Comment::class;

    public function fields(): array
    {
        return [
            'body' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::type('User'),
            'article' => Bakery::type('Article'),
        ];
    }

    public $lookupFields = ['slug'];
}
