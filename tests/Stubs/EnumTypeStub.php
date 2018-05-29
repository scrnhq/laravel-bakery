<?php

namespace Bakery\Tests\Stubs;

use Bakery\Types\EnumType;
use GraphQL\Type\Definition\Type;

class EnumTypeStub extends EnumType
{
    protected $name = 'EnumStub';

    public function values(): array
    {
        return [
            'A' => [
                'value' => 'A',
            ],
        ];
    }

    public function fields(): array
    {
        return [
            'test' => [
                'type' => Type::string(),
            ],
        ];
    }
}
