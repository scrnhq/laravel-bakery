<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Article;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class ArticleDefinition
{
    use Introspectable;

    public static $model = Article::class;

    public function fields(): array
    {
        return [
            'slug' => Type::nonNull(Type::string()),
            'title' => Type::nonNull(Type::string()),
            'content' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserDefinition::class)->nullable(),
            'tags' => Bakery::collection(TagDefinition::class)->nullable(),
            'category' => Bakery::model(CategoryDefinition::class)->nullable(),
            'comments' => Bakery::collection(CommentDefinition::class),
        ];
    }

    public $lookupFields = ['slug'];
}
