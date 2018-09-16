<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;

class DummyReadOnlySchema extends ModelSchema
{
    protected $model = Model::class;

    protected $mutable = false;
}
