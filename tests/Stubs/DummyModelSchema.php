<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;
use Bakery\Field;

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
