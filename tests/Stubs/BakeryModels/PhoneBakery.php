<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Tests\Models\Phone;
use Bakery\Eloquent\ModelSchema;
use GraphQL\Type\Definition\Type;

class PhoneBakery
{
    use ModelSchema;

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
