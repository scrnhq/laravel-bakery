<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Tests\Models\Phone;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;

class PhoneBakery
{
    use Introspectable;

    public static $model = Phone::class;

    public function fields(): array
    {
        return [
            'number' => Type::nonNull(Type::string()),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::nonNull(Bakery::type('User')),
        ];
    }
}
