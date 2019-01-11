<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $attributes = [
        'admin' => false,
    ];

    public function phone()
    {
        return $this->hasOne(Phone::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)
            ->using(UserRole::class)
            ->withPivot(['admin'])
            ->withTimestamps();
    }
}
