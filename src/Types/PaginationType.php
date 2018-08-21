<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;
use Bakery\Types\Definitions\ObjectType;

class PaginationType extends ObjectType
{
    protected $name = 'Pagination';

    public function fields(): array
    {
        return [
            'total' => Bakery::int(),
            'per_page' => Bakery::int(),
            'current_page' => Bakery::int(),
            'last_page' => Bakery::int(),
            'next_page' => Bakery::int()->nullable(),
            'previous_page' => Bakery::int()->nullable(),
        ];
    }
}
