<?php

namespace Scrn\Bakery\Types;

use Scrn\Bakery\Support\Facades\Bakery;

class PaginationType extends Type
{
    protected $attributes = [
        'name' => 'Pagination',
    ];

    public function fields()
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
            'prev_page' => [
                'type' => Bakery::int(),
                'description' => 'The previous page',
            ]
        ];
    }
}
