<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\Article;

class ArticleSchema extends ModelSchema
{
    protected $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Field::string()->unique(),
            'title' => Field::string()->searchable(),
            'content' => Field::string()->searchable(),
            'created_at' => Field::type('Timestamp')->fillable(false),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Field::model(UserSchema::class)->nullable()->searchable(),
            'tags' => Field::collection(TagSchema::class)->nullable(),
            'category' => Field::model(CategorySchema::class)->nullable(),
            'comments' => Field::collection(CommentSchema::class),
            'upvotes' => Field::collection(UpvoteSchema::class),
        ];
    }
}
