<?php

namespace Bakery\Tests\Stubs\BakeryModels;

use Bakery;
use Bakery\Tests\Models\Phone;
use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

class PhoneBakery extends BakeryModel
{
    protected $model = Phone::class;

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
