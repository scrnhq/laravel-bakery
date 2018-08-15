<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Database\Eloquent\Model;
use Bakery\Contracts\Mutable as MutableContract;

class Role extends Model implements MutableContract
{
    use Mutable;

    protected $casts = [
        'id' => 'string',
    ];

    protected $fillable = [
        'name',
        'users',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(UserRole::class)
            ->withPivot('comment')
            ->withTimestamps();
    }
}
