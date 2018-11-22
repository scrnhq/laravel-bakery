<?php

namespace Bakery\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Bakery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Bakery\Bakery::class;
    }
}
