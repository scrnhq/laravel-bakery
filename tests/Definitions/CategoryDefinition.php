<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Category;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class CategoryDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = Category::class;

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
