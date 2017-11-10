<?php

namespace Scrn\Bakery\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Support\Fluent;

class Type extends Fluent
{
    protected $input = false;

    public function fields()
    {
        return [];
    }

    public function getFields()
    {
        return $this->fields();
    }

    public function getAttributes()
    {
        return array_merge($this->attributes, [
            'fields' => function () {
                return $this->fields();
            },
        ]);
    }

    public function toArray()
    {
        return $this->getAttributes();
    }

    public function toGraphQLType()
    {
        if ($this->input) {
            return new InputObjectType($this->toArray());
        }
        return new ObjectType($this->toArray());
    }
}
