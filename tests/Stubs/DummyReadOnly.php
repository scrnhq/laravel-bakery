<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;

class DummyReadOnly
{
    use ModelSchema;

    public static $readOnly = true;

    public static $model = Model::class;
}
