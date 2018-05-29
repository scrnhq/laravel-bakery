<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

use Bakery\Tests\Models\Article;

class ArticleBakery extends BakeryModel
{
    protected $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Type::nonNull(Type::string()),
            'title' => Type::nonNull(Type::string()),
            'content' => Type::nonNull(Type::string()),
            'slug' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::type('User'),
            'comments' => Bakery::nonNull(Bakery::listOf(Bakery::type('Comment'))),
        ];
    }

    public $lookupFields = ['slug'];
}
