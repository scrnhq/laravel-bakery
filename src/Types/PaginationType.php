<?php

namespace Bakery\Types;

use Bakery\Types\Definitions\ObjectType;

class PaginationType extends ObjectType
{
    protected $name = 'Pagination';

    public function fields(): array
    {
        return [
            'total' => $this->registry->field($this->registry->int()),
            'per_page' => $this->registry->field($this->registry->int()),
            'current_page' => $this->registry->field($this->registry->int()),
            'last_page' => $this->registry->field($this->registry->int()),
            'next_page' => $this->registry->field($this->registry->int())->nullable(),
            'previous_page' => $this->registry->field($this->registry->int())->nullable(),
        ];
    }
}
