<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Upvote;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class UpvoteDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = Upvote::class;

    public function fields(): array
    {
        return [
            'id' => Bakery::ID(),
        ];
    }

    public function relations(): array
    {
        return [
            'upvoteable' => Bakery::polymorphic([ArticleDefinition::class, CommentDefinition::class]),
        ];
    }
}
