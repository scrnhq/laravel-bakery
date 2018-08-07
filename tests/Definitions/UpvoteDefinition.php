<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Upvote;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class UpvoteDefinition
{
    use Introspectable;

    public static $model = Upvote::class;

    public function fields(): array
    {
        return [];
    }

    public function relations(): array
    {
        return [
            'upvoteable' => Bakery::polymorphic([ArticleDefinition::class, CommentDefinition::class]),
        ];
    }
}
