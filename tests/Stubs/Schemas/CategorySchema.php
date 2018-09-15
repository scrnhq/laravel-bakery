<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Category;

class CategorySchema extends ModelSchema
{
    protected $model = Category::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [];
    }
}
