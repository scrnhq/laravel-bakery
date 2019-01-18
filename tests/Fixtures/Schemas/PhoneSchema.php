<?php

namespace Bakery\Tests\Fixtures\Schemas;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;
use Bakery\Tests\Fixtures\Models\Phone;

class PhoneSchema extends ModelSchema
{
    protected $model = Phone::class;

    protected $indexable = false;

    public function fields(): array
    {
        return [
            'number' => Field::string(),
        ];
    }

    public function relations(): array
    {
        return [
            'user' => Field::model(UserSchema::class),
        ];
    }
}
