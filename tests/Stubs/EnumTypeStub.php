<?php

namespace Scrn\Bakery\Tests\Stubs;

use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Types\EnumType;

class EnumTypeStub extends EnumType
{
    protected $attributes = [
        'name' => 'EnumStub',
    ];

    public function values(): array
    {
        return [
            'A' => [
                'value' => 'A',
            ]
        ];
    }

    public function fields(): array
    {
        return [
            'test' => [
                'type' => Type::string(),
            ]
        ];
    }
}
