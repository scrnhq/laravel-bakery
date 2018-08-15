<?php

namespace Bakery\Tests\Definitions;

use Bakery\Tests\Models\Phone;
use Bakery\Support\Facades\Bakery;
use Bakery\Eloquent\Introspectable;
use Bakery\Contracts\Introspectable as IntrospectableContract;

class PhoneDefinition implements IntrospectableContract
{
    use Introspectable;

    public static $model = Phone::class;

    public function fields(): array
    {
        return [
            'number' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserDefinition::class),
        ];
    }
}
