<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\Tag;

class TagSchema extends ModelSchema
{
    protected $model = Tag::class;

    public function fields(): array
    {
        return [
            'name' => Field::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'articles' => Field::collection(ArticleSchema::class),
        ];
    }
}
