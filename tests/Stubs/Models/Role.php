<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
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
