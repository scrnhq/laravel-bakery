<?php

namespace Scrn\Bakery\Tests\Stubs;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Scrn\Bakery\Traits\GraphQLResource;

class Model extends BaseModel
{
    use GraphQLResource;

    public function fields()
    {
        return [
            'id' => Type::ID,
        ];
    }
}