<?php

namespace Bakery\Tests\Definitions;

use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Models\Upvote;
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
            'upvoteable' => Bakery::polymorhpic([ArticleDefinition::class, CommentDefinition::class]),
        ];
    }
}
