<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;

class DummyReadOnlySchema extends ModelSchema
{
    protected $model = DummyModel::class;

    protected $mutable = false;
}
