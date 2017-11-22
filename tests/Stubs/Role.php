<?php

namespace Bakery\Tests\Stubs;

use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model as BaseModel;

use Bakery\Tests\Stubs\User;
use Bakery\Support\Facades\Bakery;
use Bakery\Traits\GraphQLResource;

class Role extends BaseModel
{
    use GraphQLResource;

    /**
     * Define the fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'users',
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
        ];
    }

    /**
     * The relations of the role.
     *
     * @return array
     */
    public function relations()
    {
        return [
            'users' => Bakery::listOf(Bakery::type('User')),
        ];
    }

    /**
     * A role belongs to many users.
     *
     * @return Relations\BelongsToMany;
     */
    public function users(): Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
