<?php

namespace Bakery\Support;

class DefaultSchema extends Schema
{
    public function models()
    {
        return config('bakery.types', []);
    }
}
