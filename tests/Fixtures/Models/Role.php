<?php

namespace Bakery\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $keyType = 'string';

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->as($_SERVER['eloquent.role.users.pivot'] ?? 'pivot')
            ->using(UserRole::class)
            ->withPivot(['admin'])
            ->withTimestamps();
    }
}
