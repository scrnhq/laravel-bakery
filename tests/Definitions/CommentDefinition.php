<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Comment;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class CommentDefinition
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
            'user' => Bakery::model(UserDefinition::class),
            'article' => Bakery::model(ArticleDefinition::class),
        ];
    }

    public $lookupFields = ['slug'];
}
