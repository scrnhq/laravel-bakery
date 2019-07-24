<?php

namespace Bakery\Tests\Fixtures\Types;

use Bakery\Field;
use Bakery\Types\Definitions\InputType;

class InviteUserInputType extends InputType
{
    public function fields(): array
    {
        return [
            'email' => Field::string(),
        ];
    }
}
