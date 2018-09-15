<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Tag;

class TagSchema extends ModelSchema
{
    protected $model = Tag::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Bakery::collection(ArticleSchema::class),
        ];
    }
}
