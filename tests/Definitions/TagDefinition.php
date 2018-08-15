<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Tag;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class TagDefinition implements IntrospectableContract
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
