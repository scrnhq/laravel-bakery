<?php

namespace Bakery\Tests\Stubs;

use Bakery\Support\Facades\Bakery;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Bakery\Traits\GraphQLResource;

class Model extends BaseModel
{
    use GraphQLResource;

    /**
     * Define the fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'lower_case',
    ];

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
            'field' => Bakery::string(),
            'title' => Bakery::string(),
            'body' => Bakery::string(),
            'lower_case' => Bakery::string(),
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
