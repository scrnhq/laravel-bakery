<?php

namespace Bakery\Types;

use GraphQL\Type\Definition\Type;
use Bakery\Support\Facades\Bakery;
use Illuminate\Support\Collection;

class AttachPivotInputType extends MutationInputType
{
    /**
     * Get the name of the input type.
     *
     * @return string
     */
    protected function name(): string
    {
        return 'Attach'.$this->schema->typename().'Input';
    }

    /**
     * Return the fields for the input type.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'id' => Type::ID(),
            'pivot' => Bakery::type('Create'.$this->schema->typename().'Input'),
        ];
    }
}
