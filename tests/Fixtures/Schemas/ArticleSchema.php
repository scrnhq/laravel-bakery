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
            'title' => Field::string()->searchable()->sortable(),
            'name' => Field::string()->accessor('title')->searchable()->sortable()->readOnly(),
            'authorName' => Field::string()->with('user')->readOnly()->resolve(function (Article $article) {
                return $article->user->name;
            }),
            'created_at' => Field::type('Timestamp')->readOnly(),
            'createdAt' => Field::type('Timestamp')->accessor('created_at')->readOnly(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Field::model(UserSchema::class)->nullable()->searchable(),
            'author' => Field::model(UserSchema::class)->nullable()->searchable()->accessor('user'),
            'tags' => Field::collection(TagSchema::class),
            'comments' => Field::collection(CommentSchema::class),
            'remarks' => Field::collection(CommentSchema::class)->accessor('comments'),
            'myComments' => Field::collection(CommentSchema::class)->relation(function (Article $article) {
                return $article->comments()->where('author_id', optional(auth()->user())->getAuthIdentifier());
            }),
        ];
    }
}
