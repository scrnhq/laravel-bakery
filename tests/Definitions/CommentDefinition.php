<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Comment;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class CommentDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = Comment::class;

    public function fields(): array
    {
        return [
            'body' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserDefinition::class),
            'article' => Bakery::model(ArticleDefinition::class),
            'upvotes' => Bakery::collection(UpvoteDefinition::class),
        ];
    }

    public $lookupFields = ['slug'];
}
