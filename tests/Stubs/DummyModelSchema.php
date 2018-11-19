<?php

namespace Bakery\Tests\Stubs;

use Bakery\Field;
use Bakery\Eloquent\ModelSchema;

class DummyModelSchema extends ModelSchema
{
    protected $model = DummyModel::class;

    public function fields(): array
    {
        return [
            'name' => Field::string(),
        ];
    }
}
