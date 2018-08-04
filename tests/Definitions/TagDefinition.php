<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Tag;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class TagDefinition
{
    use Introspectable;

    public static $model = Tag::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }
}
