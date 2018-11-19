<?php

namespace Bakery\Tests\Stubs\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $attributes = [
        'type' => 'regular',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public $fillable = [
        'name',
        'email',
        'name',
        'password',
        'phone',
        'type',
        'customRoles',
        'articles',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function phone()
    {
        return $this->hasOne(Phone::class);
    }

    public function customRoles()
    {
        return $this->belongsToMany(Role::class)
            ->as('customPivot')
            ->using(UserRole::class)
            ->withPivot('comment')
            ->withTimestamps();
    }
}
