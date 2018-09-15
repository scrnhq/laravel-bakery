<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Upvote;

class UpvoteSchema extends ModelSchema
{
    protected $model = Upvote::class;

    public function fields(): array
    {
        return [];
    }

    public function relations(): array
    {
        return [
            'upvoteable' => Bakery::polymorphic([ArticleSchema::class, CommentSchema::class]),
        ];
    }
}
