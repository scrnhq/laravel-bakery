<?php

namespace Scrn\Bakery\Tests\Stubs;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Scrn\Bakery\Traits\GraphQLResource;

class Model extends BaseModel
{
    use GraphQLResource;

    /**
     * The fields exposed in GraphQL.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'id' => Type::ID(),
            'field' => Type::string(),
        ];
    }
}
