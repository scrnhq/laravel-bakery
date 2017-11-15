<?php

namespace Scrn\Bakery\Tests\Stubs;

use Bakery;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Tests\Stubs\Comment;
use Scrn\Bakery\Traits\GraphQLResource;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Phone extends BaseModel
{
    use GraphQLResource;

    /**
     * Define the fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'user',
    ];

    /**
     * The fields exposed in GraphQL.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'id' => Type::ID(),
            'number' => Type::string(),
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
            'number' => Type::string(),
        ];
    }

    /**
     * Get the comments for the post.
     * 
     * @return Relations\HasOne;
     */
    public function user(): Relations\HasOne
    {
        return $this->hasOne(User::class);
    }
}
