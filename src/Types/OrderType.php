<?php

namespace Bakery\Types;

use Bakery\Types\Definitions\EnumType;

class OrderType extends EnumType
{
    protected $name = 'Order';

    public function values(): array
    {
        return ['DESC', 'ASC'];
    }
}
