<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery\Eloquent\Mutable;
use Bakery\Tests\Models\Tag;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;

class TagDefinition
{
    use Introspectable;

    public static $model = Tag::class;

    public function fields(): array
    {
        return [
            'name' => Type::string(),
        ];
    }
}
