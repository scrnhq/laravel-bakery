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
            'slug' => Type::string(),
            'field' => Type::string(),
            'title' => Type::string(),
            'body' => Type::string(),
        ];
    }

    /**
     * The fields that can be used to look up the resource.
     *
     * @return array
     */
    public function lookupFields()
    {
        return [
            'slug' => Type::string(),
        ];
    }
}
