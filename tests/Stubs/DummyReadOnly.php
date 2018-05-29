<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\BakeryModel;

class DummyReadOnly extends BakeryModel
{
    public static $readOnly = true;

    protected $model = Model::class;
}
