<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\Mutable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Mutable;

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
        'roles',
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

    public function roles()
    {
        return $this->belongsToMany(Role::class)
            ->as('customPivot')
            ->using(UserRole::class)
            ->withPivot('comment')
            ->withTimestamps();
    }
}
