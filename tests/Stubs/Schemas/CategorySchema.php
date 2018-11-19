<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Stubs\Models\Category;

class CategorySchema extends ModelSchema
{
    protected $model = Category::class;

    public function fields(): array
    {
        return [
            'name' => Field::string(),
        ];
    }

    public function relations(): array
    {
        return [];
    }
}
