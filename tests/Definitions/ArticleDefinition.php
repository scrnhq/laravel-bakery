<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Article;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class ArticleDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Bakery::string()->unique(),
            'title' => Bakery::string(),
            'content' => Bakery::string(),
            'created_at' => Bakery::type('Timestamp'),
            'polymorphic' => Bakery::polymorphic([Bakery::string(), Bakery::int()]),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserDefinition::class)->nullable(),
            'tags' => Bakery::collection(TagDefinition::class)->nullable(),
            'category' => Bakery::model(CategoryDefinition::class)->nullable(),
            'comments' => Bakery::collection(CommentDefinition::class),
            'upvotes' => Bakery::collection(UpvoteDefinition::class),
        ];
    }
}
