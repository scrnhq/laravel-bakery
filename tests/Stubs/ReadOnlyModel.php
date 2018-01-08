<?php

namespace Bakery\Tests\Stubs;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Bakery\Traits\GraphQLResource;

class ReadOnlyModel extends BaseModel
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
            'id' => Bakery::ID(),
            'slug' => Bakery::string(),
            'title' => Bakery::string(),
            'body' => Bakery::string(),
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
            'slug' => Bakery::string(),
        ];
    }
}
