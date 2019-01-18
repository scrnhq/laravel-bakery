<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\Article;

class ArticleSchema extends ModelSchema
{
    protected $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Field::string()->unique(),
            'title' => Field::string()->searchable(),
            'created_at' => Field::type('Timestamp')->readOnly(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Field::model(UserSchema::class)->nullable()->searchable(),
            'tags' => Field::collection(TagSchema::class),
            'comments' => Field::collection(CommentSchema::class),
        ];
    }
}
