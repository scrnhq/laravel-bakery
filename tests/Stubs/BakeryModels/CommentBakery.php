<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

use Bakery\Tests\Models\Comment;

class CommentBakery extends BakeryModel
{
    protected $model = Comment::class;

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
