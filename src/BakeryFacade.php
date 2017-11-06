<?php

namespace Scrn\Bakery;

use Illuminate\Support\Facades\Facade;

class BakeryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bakery';
    }
}
