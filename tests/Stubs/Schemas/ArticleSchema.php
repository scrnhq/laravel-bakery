<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Article;

class ArticleSchema extends ModelSchema
{
    protected $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Bakery::string()->unique(),
            'title' => Bakery::string(),
            'content' => Bakery::string(),
            'created_at' => Bakery::type('Timestamp')->fillable(false),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserSchema::class)->nullable(),
            'tags' => Bakery::collection(TagSchema::class)->nullable(),
            'category' => Bakery::model(CategorySchema::class)->nullable(),
            'comments' => Bakery::collection(CommentSchema::class),
            'upvotes' => Bakery::collection(UpvoteSchema::class),
        ];
    }
}
