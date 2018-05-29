<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\BakeryModel;
use Bakery\Support\Facades\Bakery;
use Bakery\Traits\GraphQLResource;

class DummyReadOnly extends BakeryModel
{
    public static $readOnly = true;

    protected $model = Model::class;
}
