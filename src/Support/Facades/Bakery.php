<?php

namespace Scrn\Bakery\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Bakery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bakery';
    }
}
