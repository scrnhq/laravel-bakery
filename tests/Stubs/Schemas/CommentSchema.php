<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\Comment;

class CommentSchema extends ModelSchema
{
    protected $model = Comment::class;

    public function fields(): array
    {
        return [
            'body' => Field::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Field::model(UserSchema::class),
            'article' => Field::model(ArticleSchema::class),
            'upvotes' => Field::collection(UpvoteSchema::class),
        ];
    }
}
