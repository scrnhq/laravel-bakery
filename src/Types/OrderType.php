<?php

namespace Bakery\Types;

class OrderType extends EnumType
{
    protected $name = 'Order';

    public function values(): array
    {
        return ['DESC', 'ASC'];
    }
}
