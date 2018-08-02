<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Phone;
use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;

class PhoneDefinition
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
            'user' => Bakery::model(UserDefinition::class),
        ];
    }
}
