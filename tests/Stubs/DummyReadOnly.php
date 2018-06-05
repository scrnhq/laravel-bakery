<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\Introspectable;

class DummyReadOnly
{
    use Introspectable;

    public static $readOnly = true;

    public static $model = Model::class;
}
