<?php

namespace Scrn\Bakery\Tests\Stubs;

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
     * Get the comments for the post.
     * 
     * @return Relations\BelongsTo;
     */
    public function post(): Relations\BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
