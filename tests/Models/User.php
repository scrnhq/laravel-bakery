<?php

namespace Bakery\Tests\Models;

use Bakery\Eloquent\BakeryMutable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use BakeryMutable;

    protected $casts = [
        'id' => 'string',
    ];

    public $fillable = [
        'name',
        'email',
        'name',
        'password',
        'phone',
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
        return $this->belongsToMany(Role::class);
    }
}
