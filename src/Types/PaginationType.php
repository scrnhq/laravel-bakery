<?php

namespace Bakery\Types;

use Bakery\Support\Facades\Bakery;

class PaginationType extends Type
{
    protected $name = 'Pagination';

    public function fields(): array
    {
        return [
            'total' => [
                'type' => Bakery::int(),
                'description' => 'The total number of items',
            ],
            'per_page' => [
                'type' => Bakery::int(),
                'description' => 'The amount on a page',
            ],
            'current_page' => [
                'type' => Bakery::int(),
                'description' => 'The current page',
            ],
            'last_page' => [
                'type' => Bakery::int(),
                'description' => 'The last page',
            ],
            'next_page' => [
                'type' => Bakery::int(),
                'description' => 'The next page',
            ],
            'previous_page' => [
                'type' => Bakery::int(),
                'description' => 'The previous page',
            ]
        ];
    }
}
