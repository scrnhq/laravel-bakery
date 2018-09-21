<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\Tag;

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
