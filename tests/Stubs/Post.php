<?php

namespace Bakery\Tests\Stubs;

use Bakery;
use GraphQL\Type\Definition\Type;
use Bakery\Tests\Stubs\Comment;
use Bakery\Traits\GraphQLResource;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Post extends BaseModel
{
    use GraphQLResource;

    /**
     * Define the fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'body',
        'slug',
        'comments',
        'user',
    ];

    /**
     * The fields exposed in GraphQL.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            'id' => Type::ID(),
            'slug' => Type::string(),
            'title' => Type::string(),
            'body' => Type::string(),
        ];
    }

    /**
     * The relations exposed in GraphQL.
     *
     * @return array
     */
    public function relations(): array
    {
        return [
            'comments' => Bakery::listOf(Bakery::getType('Comment')),
            'user' => Bakery::type('User'),
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

    /**
     * Get the comments for the post.
     *
     * @return Relations\HasMany;
     */
    public function comments(): Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * A post belongs to a user.
     *
     * @return Relations\HasMany;
     */
    public function user(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
