<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Tests\Models\Category;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class CategoryBakery
{
    use Introspectable;

    public static $model = Category::class;

    public function fields(): array
    {
        return [
            'name' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [];
    }
}
