<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\Comment;

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
            'author' => Field::model(UserSchema::class),
            'commentable' => Field::polymorphic([
                ArticleSchema::class
            ]),
        ];
    }
}
