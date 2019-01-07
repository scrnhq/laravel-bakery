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

    public function customRoles()
    {
        return $this->belongsToMany(Role::class)->as('customPivot')
            ->withPivot(['admin'])
            ->using(UserRole::class)
            ->withTimestamps();
    }
}
