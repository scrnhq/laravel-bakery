<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\ModelSchema;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\BakeryMutable;
use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    use ModelSchema;
    use BakeryMutable;

    public function fields(): array
    {
        return [
            'foo' => Type::string(),
        ];
    }
}
