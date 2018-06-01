<?php

namespace Bakery\Tests\Stubs;

use Bakery\Eloquent\Mutable;
use GraphQL\Type\Definition\Type;
use Bakery\Eloquent\Introspectable;
use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    use Mutable;
    use Introspectable;

    public function fields(): array
    {
        return [
            'foo' => Type::string(),
        ];
    }
}
