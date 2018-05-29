<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\BakeryModel;
use GraphQL\Type\Definition\Type;

class DummyInheritClass extends BakeryModel
{
    protected $model = Model::class;

    public function fields(): array
    {
        return [
            'foo' => Type::string(),
        ];
    }
}
