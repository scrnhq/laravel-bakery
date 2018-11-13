<?php

namespace Bakery\Tests\Stubs;

use Bakery\Type;
use Bakery\Types\Definitions\EnumType;

class EnumTypeStub extends EnumType
{
    public $name = 'EnumStub';

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
