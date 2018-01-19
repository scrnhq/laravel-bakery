<?php

namespace Bakery\Tests\Stubs;

use Bakery;
use Bakery\Traits\GraphQLResource;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use GraphQLResource;

    /**
     * The default attributes for the user model.
     *
     * @var array
     */
    protected $attributes = [
        'password' => 'secret'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'roles',
        'posts',
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
            'name' => Type::string(),
            'email' => Type::string(),
            'password' => Type::string(),
            'secret_information' => [
                'type' => Type::string(),
                'readable' => function ($user, $args, $viewer) {
                    return $user->is($viewer);
                }
            ]
        ];
    }

    /**
     * The fields that can be used to lookup this model.
     *
     * @return array
     */
    public function lookupFields()
    {
        return [
            'email' => Type::string(),
        ];
    }

    /**
     * The relationships exposed in GraphQL.
     *
     * @return array
     */
    public function relations()
    {
        return [
            'posts' => Bakery::listOf(Bakery::getType('Post')),
            'roles' => Bakery::listOf(Bakery::getType('Role')),
            'phone' => Bakery::getType('Phone'),
        ];
    }

    /**
     * Encrypts the password before setting in to the user.
     *
     * @param string $password the plaintext password
     * @return void
     */
    public function setPasswordAttribute(string $password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    /**
     * A user has a phone.
     *
     * @return Relations\HasOne
     */
    public function phone(): Relations\HasOne
    {
        return $this->hasOne(Phone::class);
    }

    /**
     * A role belongs to many users.
     *
     * @return Relations\BelongsToMany;
     */
    public function roles(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * A user has many posts.
     *
     * @return Relations\HasMany;
     */
    public function posts(): Relations\HasMany
    {
        return $this->hasMany(Post::class);
    }
}
