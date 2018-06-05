<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
    use Mutable;
}
