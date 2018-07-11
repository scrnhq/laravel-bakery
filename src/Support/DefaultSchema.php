<?php

namespace Bakery\Support;

use Bakery\Utils\Utils;

class DefaultSchema extends Schema
{
    public function models()
    {
        $models = config('bakery.models');

        Utils::invariant(count($models) > 0, 'There must be models defined in the Bakery config.');

        return $models;
    }
}
