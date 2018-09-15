<?php

namespace Bakery\Tests\Stubs\Schemas;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;
use Bakery\Tests\Stubs\Models\Phone;

class PhoneSchema extends ModelSchema
{
    protected $model = Phone::class;

    public function fields(): array
    {
        return [
            'number' => Bakery::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Bakery::model(UserSchema::class),
        ];
    }
}
