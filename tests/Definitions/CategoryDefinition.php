<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Category;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;

class CategoryDefinition
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
