<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    use BakeryMutable;
}
