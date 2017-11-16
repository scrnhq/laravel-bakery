<?php

namespace Scrn\Bakery\Tests\Stubs;

use Bakery;
use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Tests\Stubs\Post;
use Scrn\Bakery\Traits\GraphQLResource;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Comment extends BaseModel
{
    use GraphQLResource;

    /**
     * Define the fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'body',
        'post',
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
            'body' => Type::string(),
        ];
    }

    /**
     * The relations of the GraphQL object.
     * 
     * @return array
     */
    public function relations()
    {
        return [
            'post' => Bakery::type('Post'),
            'user' => Bakery::type('User'),
        ];
    }

    /**
     * A comment belongs to a post. 
     * 
     * @return Relations\BelongsTo;
     */
    public function post(): Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * A comment belongs to a user.
     * 
     * @return Relations\BelongsTo;
     */
    public function user(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
