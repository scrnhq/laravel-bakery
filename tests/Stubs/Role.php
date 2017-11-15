<?php

namespace Scrn\Bakery\Tests\Stubs;

use GraphQL\Type\Definition\Type;
use Scrn\Bakery\Tests\Stubs\User;
use Scrn\Bakery\Traits\GraphQLResource;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model as BaseModel;

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
     * A role belongs to many users. 
     * 
     * @return Relations\BelongsToMany;
     */
    public function users(): Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
