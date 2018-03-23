<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;

class OrderType extends EnumType
{
    protected $name = 'Order';

    public function values(): array
    {
        return ['DESC', 'ASC'];
    }
}
