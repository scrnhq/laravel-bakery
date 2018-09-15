<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Comment;

class CommentSchema extends ModelSchema
{
    protected $model = Comment::class;

    public function fields(): array
    {
        return [
            'body' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserSchema::class),
            'article' => Bakery::model(ArticleSchema::class),
            'upvotes' => Bakery::collection(UpvoteSchema::class),
        ];
    }
}
