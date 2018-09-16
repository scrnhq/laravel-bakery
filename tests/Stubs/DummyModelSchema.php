<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;
use Bakery\Support\Facades\Bakery;

class DummyModelSchema extends ModelSchema
{
    protected $model = DummyModel::class;

    public function fields(): array
    {
        return [
            'name' => Bakery::string(),
        ];
    }
}
